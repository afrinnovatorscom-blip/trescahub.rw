<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Support\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class VendorAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('vendors.view'), 403);

        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['verified', 'unverified'])],
        ]);
        $activeTab = $filters['tab'] ?? 'verified';
        $vendors = null;
        $applications = null;

        if ($activeTab === 'verified') {
            $vendorQuery = Vendor::query();
            if (Schema::hasColumn($vendorQuery->getModel()->getTable(), 'application_status')) {
                $vendorQuery->where(function ($query) {
                    $query->whereNull('application_status')
                        ->orWhere('application_status', '')
                        ->orWhere('application_status', 'approved');
                });
            } else {
                $vendorQuery->where('status', '!=', 'pending');
            }

            $vendors = $vendorQuery
                ->withCount(['products', 'users'])
                ->when($request->q, fn ($query) => $query->where('business_name', 'like', '%'.$request->q.'%'))
                ->when($request->status, fn ($query) => $query->where('status', $request->status))
                ->latest()
                ->paginate(15)
                ->withQueryString();
        } else {
            $applications = Vendor::query()
                ->whereNotNull('application_submitted_at')
                ->whereIn('application_status', ['pending', 'correction_requested'])
                ->when($request->q, fn ($query) => $query->where(function ($query) use ($request) {
                    $query->where('business_name', 'like', '%'.$request->q.'%')
                        ->orWhere('contact_person', 'like', '%'.$request->q.'%')
                        ->orWhere('tin', 'like', '%'.$request->q.'%')
                        ->orWhere('phone', 'like', '%'.$request->q.'%')
                        ->orWhere('business_email', 'like', '%'.$request->q.'%');
                }))
                ->orderByDesc('application_submitted_at')
                ->paginate(15)
                ->withQueryString();
        }

        $summary = [
            'total' => Vendor::query()->count(),
            'active' => Vendor::query()->where('status', 'active')->count(),
            'terminated' => Vendor::query()->where('status', 'inactive')->count(),
        ];

        return view('marketplace.vendors.index', compact('vendors', 'applications', 'summary', 'activeTab'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('vendors.create'), 403);

        return view('marketplace.vendors.form', [
            'vendor' => new Vendor(['status' => 'pending']),
            'account' => null,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('vendors.create'), 403);

        $data = $this->validated($request);
        $logoPath = null;
        $certificatePath = null;

        try {
            if ($request->hasFile('business_logo')) {
                $logoPath = PublicAsset::store($request->file('business_logo'), 'vendors/logos');
            }

            $certificatePath = $request->file('rdb_certificate')?->store('vendor-certificates', 'local');
            if (! is_string($certificatePath) || $certificatePath === '') {
                throw new RuntimeException('The RDB certificate could not be stored securely.');
            }

            [$vendor, $user] = DB::transaction(function () use ($data, $logoPath, $certificatePath) {
                $vendorRole = Role::query()->where('slug', 'vendor')->first();
                if (! $vendorRole) {
                    throw new RuntimeException('The vendor role is not configured.');
                }

                $attributes = $this->vendorAttributes($data, $logoPath);
                $attributes['registration_certificate_path'] = $certificatePath;
                $vendor = Vendor::query()->create($attributes);
                $user = $vendor->users()->create([
                    'name' => $vendor->contact_person,
                    'email' => $vendor->email,
                    'phone' => null,
                    'password' => $data['password'],
                    'email_verified_at' => now(),
                ]);
                $user->roles()->sync([$vendorRole->id]);
                $user->forgetPermissionCache();

                return [$vendor, $user];
            });
        } catch (Throwable $exception) {
            if ($certificatePath) {
                Storage::disk('local')->delete($certificatePath);
            }
            PublicAsset::delete($logoPath);
            throw $exception;
        }

        AuditLogger::log($request->user(), 'vendor.created', $vendor, [
            'business_name' => $vendor->business_name,
            'user_id' => $user->id,
        ]);

        return redirect()->route('marketplace.vendors.show', $vendor)->with('success', 'Vendor registered.');
    }

    public function show(Vendor $vendor)
    {
        abort_unless(auth()->user()->hasPermission('vendors.view'), 403);

        $vendor->loadCount('products');
        $orderCount = $vendor->orderItems()->whereHas('order')->distinct('order_id')->count('order_id');
        $salesTotal = $vendor->orderItems()
            ->whereHas('order', fn ($query) => $query->where('payment_status', 'paid'))
            ->whereNotIn('fulfillment_status', ['cancelled', 'returned'])
            ->sum('line_total');
        $availableStock = $vendor->products()
            ->where('tracks_inventory', true)
            ->get(['stock_quantity', 'reserved_quantity'])
            ->sum(fn ($product) => $product->availableQuantity());
        $products = $vendor->products()->with('category')->latest()->paginate(10, ['*'], 'products_page')->withQueryString();
        $orders = Order::query()
            ->whereHas('items', fn ($query) => $query->where('vendor_id', $vendor->id))
            ->with(['items' => fn ($query) => $query->where('vendor_id', $vendor->id)])
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();
        $account = $vendor->users()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'vendor'))
            ->orderBy('id')
            ->first();
        $certificateExists = filled($vendor->registration_certificate_path)
            && Storage::disk('local')->exists($vendor->registration_certificate_path);

        AuditLogger::log(auth()->user(), 'vendor.viewed', $vendor);

        return view('marketplace.vendors.show', compact(
            'vendor',
            'orderCount',
            'salesTotal',
            'availableStock',
            'products',
            'orders',
            'account',
            'certificateExists',
        ));
    }

    public function edit(Vendor $vendor)
    {
        abort_unless(auth()->user()->hasPermission('vendors.manage'), 403);
        $account = $vendor->users()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'vendor'))
            ->orderBy('id')
            ->first();

        return view('marketplace.vendors.form', compact('vendor', 'account'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $account = $vendor->users()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'vendor'))
            ->orderBy('id')
            ->first();
        $data = $this->validated($request, $vendor, $account);
        $this->ensureApplicationApprovedBeforeActivation($vendor, $data['status']);
        $oldLogoPath = $vendor->business_logo_path;
        $oldCertificatePath = $vendor->registration_certificate_path;
        $brandingApprovalBefore = $vendor->public_branding_approved;
        $newLogoPath = null;
        $newCertificatePath = null;

        $before = $vendor->status;
        try {
            if ($request->hasFile('business_logo')) {
                $newLogoPath = PublicAsset::store($request->file('business_logo'), 'vendors/logos');
            }
            if ($request->hasFile('rdb_certificate')) {
                $newCertificatePath = $request->file('rdb_certificate')->store('vendor-certificates', 'local');
                if (! is_string($newCertificatePath) || $newCertificatePath === '') {
                    throw new RuntimeException('The RDB certificate could not be stored securely.');
                }
            }

            DB::transaction(function () use ($request, $vendor, $account, $data, $newLogoPath, $newCertificatePath) {
                $attributes = $this->vendorAttributes(
                    $data,
                    $newLogoPath ?? ($request->boolean('remove_logo') ? null : $vendor->business_logo_path),
                    $vendor,
                );
                $attributes['registration_certificate_path'] = $newCertificatePath
                    ?? ($request->boolean('remove_certificate') ? null : $vendor->registration_certificate_path);
                $brandingChanged = $data['business_name'] !== $vendor->business_name
                    || $newLogoPath !== null
                    || ($request->boolean('remove_logo') && $vendor->business_logo_path !== null);
                $attributes['public_branding_approved'] = $brandingChanged
                    ? false
                    : $data['public_branding_approved'];
                $vendor->update($attributes);

                if ($account) {
                    $accountAttributes = [
                        'name' => $vendor->contact_person ?: $vendor->business_name,
                        'email' => $vendor->email,
                    ];
                    if (filled($data['password'] ?? null)) {
                        $accountAttributes['password'] = $data['password'];
                    }
                    $account->update($accountAttributes);
                } elseif (filled($data['password'] ?? null)) {
                    $vendorRole = Role::query()->where('slug', 'vendor')->first();
                    if (! $vendorRole) {
                        throw new RuntimeException('The vendor role is not configured.');
                    }

                    $account = $vendor->users()->create([
                        'name' => $vendor->contact_person ?: $vendor->business_name,
                        'email' => $vendor->email,
                        'phone' => null,
                        'password' => $data['password'],
                        'email_verified_at' => now(),
                    ]);
                    $account->roles()->sync([$vendorRole->id]);
                    $account->forgetPermissionCache();
                }
            });
        } catch (Throwable $exception) {
            PublicAsset::delete($newLogoPath);
            if ($newCertificatePath) {
                Storage::disk('local')->delete($newCertificatePath);
            }
            throw $exception;
        }

        if ($newLogoPath || $request->boolean('remove_logo')) {
            PublicAsset::delete($oldLogoPath);
        }
        if ($oldCertificatePath && ($newCertificatePath || $request->boolean('remove_certificate'))) {
            Storage::disk('local')->delete($oldCertificatePath);
        }

        AuditLogger::log($request->user(), 'vendor.updated', $vendor, [
            'status_before' => $before,
            'status_after' => $vendor->status,
            'public_branding_approved_before' => $brandingApprovalBefore,
            'public_branding_approved_after' => $vendor->public_branding_approved,
        ]);

        return redirect()->route('marketplace.vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    public function status(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'active', 'suspended', 'inactive'])]]);
        $this->ensureApplicationApprovedBeforeActivation($vendor, $data['status']);
        $before = $vendor->status;
        $vendor->update(['status' => $data['status']]);
        AuditLogger::log($request->user(), 'vendor.status_changed', $vendor, ['from' => $before, 'to' => $vendor->status]);

        return back()->with('success', 'Vendor status updated.');
    }

    public function publication(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $data = $request->validate([
            'action' => ['required', Rule::in(['publish', 'unpublish'])],
            'confirmed' => ['required', 'accepted'],
        ]);

        return DB::transaction(function () use ($request, $vendor, $data) {
            $lockedVendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);
            $publish = $data['action'] === 'publish';

            if ($publish && ($reason = $lockedVendor->publicationBlockReason())) {
                return back()->withErrors(['publication' => $reason]);
            }

            $previousStatus = $lockedVendor->partner_display_status;
            $nextStatus = $publish ? 'published' : 'draft';

            if ($previousStatus !== $nextStatus) {
                $lockedVendor->update(['partner_display_status' => $nextStatus]);
                AuditLogger::log($request->user(), $publish ? 'vendor.published' : 'vendor.unpublished', $lockedVendor, [
                    'partner_display_status' => ['from' => $previousStatus, 'to' => $nextStatus],
                ]);
            }

            $message = $publish
                ? 'Vendor published on the website.'
                : 'Vendor removed from the website.';

            return redirect()->route('marketplace.vendors.show', $lockedVendor)->with('success', $message);
        });
    }

    public function terminate(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);
        $request->validate(['confirmed' => ['required', 'accepted']]);

        if ($vendor->status !== 'suspended') {
            $before = $vendor->status;
            $vendor->update(['status' => 'suspended']);
            AuditLogger::log($request->user(), 'vendor.suspended', $vendor, [
                'from' => $before,
                'to' => 'suspended',
                'reason' => 'Terminated from vendor details.',
            ]);
        }

        return redirect()->route('marketplace.vendors.show', $vendor)
            ->with('success', 'Vendor operational access suspended. An authorized administrator can change the vendor status to reactivate access.');
    }

    private function validated(Request $request, ?Vendor $vendor = null, ?User $account = null): array
    {
        if (! $request->exists('account_email') && $request->exists('email')) {
            $request->merge(['account_email' => $request->input('email')]);
        }
        if (! $request->exists('business_phone') && $request->exists('phone')) {
            $request->merge(['business_phone' => $request->input('phone')]);
        }

        if ($vendor) {
            $defaults = [
                'contact_person' => $vendor->contact_person ?: ($account?->name ?? $vendor->business_name),
                'account_email' => $account?->email ?? $vendor->email,
                'business_name' => $vendor->business_name,
                'legal_name' => $vendor->legal_name,
                'tin' => $vendor->tin,
                'business_phone' => $vendor->phone,
                'business_email' => $vendor->business_email,
                'business_address' => $vendor->business_address,
                'bank_name' => $vendor->bank_name,
                'bank_account_holder' => $vendor->bank_account_holder,
                'bank_account_number' => $vendor->bank_account_number,
                'mobile_money_provider' => $vendor->mobile_money_provider,
                'mobile_money_phone' => $vendor->mobile_money_phone,
                'mobile_money_account_holder' => $vendor->mobile_money_account_holder,
                'status' => $vendor->status,
                'district' => $vendor->district,
                'operating_location' => $vendor->operating_location,
                'website_url' => $vendor->website_url,
                'address_notes' => $vendor->address_notes,
                'internal_notes' => $vendor->internal_notes,
                'partner_description' => $vendor->partner_description,
                'display_order' => $vendor->display_order,
            ];
            foreach ($defaults as $field => $value) {
                if (! $request->exists($field)) {
                    $request->merge([$field => $value]);
                }
            }
        }

        $request->merge([
            'account_email' => strtolower((string) $request->input('account_email')),
            'business_email' => strtolower((string) $request->input('business_email')),
        ]);

        $userEmailRule = Rule::unique('users', 'email');
        if ($account) {
            $userEmailRule->ignore($account->id);
        }
        $accountEmailRules = [
            'required',
            'email',
            'max:255',
            Rule::unique('vendors', 'email')->ignore($vendor?->id),
            $userEmailRule,
        ];
        if (! $vendor || $request->input('account_email') !== ($account?->email ?? $vendor->email)) {
            $accountEmailRules[] = Rule::unique('customers', 'email');
        }

        $rules = [
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'account_email' => $accountEmailRules,
            'tin' => [$vendor ? 'nullable' : 'required', 'string', 'max:100'],
            'business_phone' => [$vendor ? 'nullable' : 'required', 'string', 'max:50'],
            'business_email' => [$vendor ? 'nullable' : 'required', 'email', 'max:255'],
            'business_address' => [$vendor ? 'nullable' : 'required', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'inactive'])],
            'district' => ['nullable', 'string', 'max:255'],
            'operating_location' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'address_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'business_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'rdb_certificate' => [$vendor ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_certificate' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'mobile_money_provider' => ['nullable', Rule::in(['mtn_momo', 'airtel_money'])],
            'mobile_money_phone' => ['nullable', 'string', 'max:50'],
            'mobile_money_account_holder' => ['nullable', 'string', 'max:255'],
            'show_as_partner' => ['nullable', 'boolean'],
            'partner_description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['required', 'integer', 'min:0'],
        ];

        $passwordRequired = ! $vendor;
        $rules['password'] = [$passwordRequired ? 'required' : 'nullable', 'string', Password::default(), 'confirmed'];
        $rules['password_confirmation'] = [$passwordRequired ? 'required' : 'nullable', 'string', 'required_with:password'];
        if ($vendor) {
            $rules['public_branding_approved'] = ['nullable', 'boolean'];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request, $vendor): void {
            $bankFields = ['bank_name', 'bank_account_holder', 'bank_account_number'];
            $mobileFields = ['mobile_money_provider', 'mobile_money_phone', 'mobile_money_account_holder'];
            $bankEntered = $this->anyPaymentFieldFilled($request, $bankFields);
            $mobileEntered = $this->anyPaymentFieldFilled($request, $mobileFields);
            $bankComplete = $this->allPaymentFieldsFilled($request, $bankFields);
            $mobileComplete = $this->allPaymentFieldsFilled($request, $mobileFields);

            if ($bankEntered && ! $bankComplete) {
                $this->addMissingPaymentFieldErrors($validator, $request, $bankFields);
            }
            if ($mobileEntered && ! $mobileComplete) {
                $this->addMissingPaymentFieldErrors($validator, $request, $mobileFields);
            }
            if (! $vendor && ! $bankComplete && ! $mobileComplete) {
                $validator->errors()->add('bank_name', 'Provide complete bank details, complete Mobile Money details, or both.');
            }
        });

        $data = $validator->validate();
        $data['show_as_partner'] = $request->boolean('show_as_partner');
        $data['remove_logo'] = $request->boolean('remove_logo');
        $data['remove_certificate'] = $request->boolean('remove_certificate');
        if ($vendor) {
            $data['public_branding_approved'] = $request->boolean('public_branding_approved');
        }

        if ($vendor && ! $this->anyPaymentFieldFilled($request, [
            'bank_name',
            'bank_account_holder',
            'bank_account_number',
            'mobile_money_provider',
            'mobile_money_phone',
            'mobile_money_account_holder',
        ])) {
            foreach ([
                'bank_name',
                'bank_account_holder',
                'bank_account_number',
                'mobile_money_provider',
                'mobile_money_phone',
                'mobile_money_account_holder',
            ] as $field) {
                $data[$field] = $vendor->{$field};
            }
        }

        return $data;
    }

    private function vendorAttributes(array $data, ?string $logoPath, ?Vendor $existingVendor = null): array
    {
        return [
            'business_name' => $data['business_name'],
            'legal_name' => $data['legal_name'] ?? null,
            'reference_number' => $existingVendor?->reference_number,
            'contact_person' => $data['contact_person'],
            'tin' => $data['tin'] ?? null,
            'email' => $data['account_email'],
            'phone' => $data['business_phone'] ?? null,
            'business_email' => $data['business_email'] ?? null,
            'business_address' => $data['business_address'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_holder' => $data['bank_account_holder'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'mobile_money_provider' => $data['mobile_money_provider'] ?? null,
            'mobile_money_phone' => $data['mobile_money_phone'] ?? null,
            'mobile_money_account_holder' => $data['mobile_money_account_holder'] ?? null,
            'status' => $data['status'],
            'district' => $data['district'] ?? null,
            'operating_location' => $data['operating_location'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'address_notes' => $data['address_notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'business_logo_path' => $logoPath,
            'show_as_partner' => $data['show_as_partner'],
            'partner_description' => $data['partner_description'] ?? null,
            'display_order' => $data['display_order'],
        ];
    }

    private function anyPaymentFieldFilled(Request $request, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                return true;
            }
        }

        return false;
    }

    private function allPaymentFieldsFilled(Request $request, array $fields): bool
    {
        foreach ($fields as $field) {
            if (! $request->filled($field)) {
                return false;
            }
        }

        return true;
    }

    private function addMissingPaymentFieldErrors($validator, Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (! $request->filled($field)) {
                $validator->errors()->add($field, 'Complete all fields for this payment method.');
            }
        }
    }

    private function ensureApplicationApprovedBeforeActivation(Vendor $vendor, string $nextStatus): void
    {
        if (
            $nextStatus === 'active'
            && filled($vendor->application_status)
            && $vendor->application_status !== 'approved'
        ) {
            throw ValidationException::withMessages([
                'status' => 'A vendor application must be approved before vendor access can be activated.',
            ]);
        }
    }
}

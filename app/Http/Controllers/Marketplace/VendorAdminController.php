<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Support\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Throwable;

class VendorAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('vendors.view'), 403);

        $vendors = Vendor::query()
            ->withCount(['products', 'users'])
            ->when($request->q, fn ($query) => $query->where('business_name', 'like', '%'.$request->q.'%'))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('marketplace.vendors.index', compact('vendors'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('vendors.create'), 403);

        return view('marketplace.vendors.form', ['vendor' => new Vendor(['status' => 'pending'])]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('vendors.create'), 403);

        $data = $this->validated($request);
        $logoPath = $request->hasFile('business_logo')
            ? PublicAsset::store($request->file('business_logo'), 'vendors/logos')
            : null;

        try {
            [$vendor, $user] = DB::transaction(function () use ($data, $logoPath) {
                $vendorRole = Role::query()->where('slug', 'vendor')->first();
                if (! $vendorRole) {
                    throw new RuntimeException('The vendor role is not configured.');
                }

                $vendor = Vendor::query()->create($this->vendorAttributes($data, $logoPath));
                $user = $vendor->users()->create([
                    'name' => $vendor->business_name,
                    'email' => $vendor->email,
                    'phone' => $vendor->phone,
                    'password' => $data['password'],
                    'email_verified_at' => now(),
                ]);
                $user->roles()->sync([$vendorRole->id]);
                $user->forgetPermissionCache();

                return [$vendor, $user];
            });
        } catch (Throwable $exception) {
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
        $activity = AuditLog::query()
            ->with('actor')
            ->where('auditable_type', $vendor->getMorphClass())
            ->where('auditable_id', $vendor->id)
            ->latest()
            ->limit(10)
            ->get();

        AuditLogger::log(auth()->user(), 'vendor.viewed', $vendor);

        return view('marketplace.vendors.show', compact(
            'vendor',
            'orderCount',
            'salesTotal',
            'availableStock',
            'products',
            'orders',
            'activity',
        ));
    }

    public function edit(Vendor $vendor)
    {
        abort_unless(auth()->user()->hasPermission('vendors.manage'), 403);

        return view('marketplace.vendors.form', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        abort_unless($request->user()->hasPermission('vendors.manage'), 403);

        $account = $vendor->users()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'vendor'))
            ->orderBy('id')
            ->first();
        $data = $this->validated($request, $vendor, $account);
        $oldLogoPath = $vendor->business_logo_path;
        $brandingApprovalBefore = $vendor->public_branding_approved;
        $newLogoPath = $request->hasFile('business_logo')
            ? PublicAsset::store($request->file('business_logo'), 'vendors/logos')
            : null;

        $before = $vendor->status;
        try {
            DB::transaction(function () use ($request, $vendor, $account, $data, $newLogoPath) {
                $attributes = $this->vendorAttributes(
                    $data,
                    $newLogoPath ?? ($request->boolean('remove_logo') ? null : $vendor->business_logo_path)
                );
                $brandingChanged = $data['business_name'] !== $vendor->business_name
                    || $newLogoPath !== null
                    || ($request->boolean('remove_logo') && $vendor->business_logo_path !== null);
                $attributes['public_branding_approved'] = $brandingChanged
                    ? false
                    : $data['public_branding_approved'];
                $vendor->update($attributes);

                if ($account) {
                    $account->update([
                        'name' => $vendor->business_name,
                        'email' => $vendor->email,
                        'phone' => $vendor->phone,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            PublicAsset::delete($newLogoPath);
            throw $exception;
        }

        if ($newLogoPath || $request->boolean('remove_logo')) {
            PublicAsset::delete($oldLogoPath);
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
        $userEmailRule = Rule::unique('users', 'email');
        if ($account) {
            $userEmailRule->ignore($account->id);
        }

        $rules = [
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100', Rule::unique('vendors', 'reference_number')->ignore($vendor?->id)],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('vendors', 'email')->ignore($vendor?->id),
                $userEmailRule,
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'inactive'])],
            'district' => ['nullable', 'string', 'max:255'],
            'operating_location' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'address_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'business_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'show_as_partner' => ['nullable', 'boolean'],
            'partner_description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['required', 'integer', 'min:0'],
        ];

        if (! $vendor) {
            $rules['password'] = ['required', 'string', Password::default()];
        } else {
            $rules['public_branding_approved'] = ['nullable', 'boolean'];
        }

        $data = $request->validate($rules);
        $data['show_as_partner'] = $request->boolean('show_as_partner');
        $data['remove_logo'] = $request->boolean('remove_logo');
        if ($vendor) {
            $data['public_branding_approved'] = $request->boolean('public_branding_approved');
        }

        return $data;
    }

    private function vendorAttributes(array $data, ?string $logoPath): array
    {
        return [
            'business_name' => $data['business_name'],
            'legal_name' => $data['legal_name'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
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
}

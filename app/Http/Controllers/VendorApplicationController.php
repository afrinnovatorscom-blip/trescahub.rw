<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceNotification;
use App\Services\MarketplaceNotifier;
use App\Support\PublicAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class VendorApplicationController extends Controller
{
    private const PAYMENT_FIELDS = [
        'bank' => ['bank_name', 'bank_account_holder', 'bank_account_number'],
        'mobile' => ['mobile_money_provider', 'mobile_money_phone', 'mobile_money_account_holder'],
    ];

    public function create(): View
    {
        return view('front.pages.vendor-registration', [
            'correctionMode' => false,
            'vendor' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower($request->string('email')->toString()),
            'business_email' => Str::lower($request->string('business_email')->toString()),
        ]);

        $validator = $this->applicationValidator($request, null);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($this->safeOldInput($request));
        }

        $data = $validator->validated();
        $certificatePath = $request->file('rdb_certificate')?->store('vendor-certificates', 'local');
        if (! is_string($certificatePath) || $certificatePath === '') {
            throw new RuntimeException('The RDB certificate could not be stored securely.');
        }

        $logoPath = null;
        try {
            if ($request->hasFile('business_logo')) {
                $logoPath = PublicAsset::store($request->file('business_logo'), 'vendors/logos');
            }

            $vendor = DB::transaction(function () use ($data, $certificatePath, $logoPath): Vendor {
                $vendorRole = Role::query()->where('slug', 'vendor')->first();
                if (! $vendorRole) {
                    throw new RuntimeException('The vendor role is not configured.');
                }

                $vendor = Vendor::query()->create($this->vendorAttributes($data) + [
                    'status' => 'pending',
                    'application_status' => 'pending',
                    'registration_certificate_path' => $certificatePath,
                    'application_submitted_at' => now(),
                    'business_logo_path' => $logoPath,
                ]);
                $user = $vendor->users()->create([
                    'name' => $data['contact_person'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'email_verified_at' => now(),
                ]);
                $user->roles()->sync([$vendorRole->id]);
                $user->forgetPermissionCache();

                return $vendor;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($certificatePath);
            PublicAsset::delete($logoPath);
            throw $exception;
        }

        app(MarketplaceNotifier::class)->vendorApplicationSubmitted($vendor);

        return redirect()->route('vendor.application.submitted')
            ->with('vendor_application_submitted', true);
    }

    public function submitted(Request $request): View|RedirectResponse
    {
        if (! $request->session()->pull('vendor_application_submitted')) {
            return redirect()->route('vendor.application.create');
        }

        return view('front.pages.vendor-application-submitted');
    }

    public function editCorrection(Request $request): View
    {
        $vendor = $this->correctionVendor($request->user());
        $correctionNotice = $this->markCorrectionNotificationsAsRead($request->user(), $vendor);

        return view('front.pages.vendor-registration', [
            'correctionMode' => true,
            'vendor' => $vendor,
            'correctionNotice' => $correctionNotice,
        ]);
    }

    public function updateCorrection(Request $request): RedirectResponse
    {
        $vendor = $this->correctionVendor($request->user());
        $request->merge([
            'business_email' => Str::lower($request->string('business_email')->toString()),
        ]);

        $validator = $this->applicationValidator($request, $vendor);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($this->safeOldInput($request));
        }

        $data = $validator->validated();
        $newCertificatePath = $request->file('rdb_certificate')?->store('vendor-certificates', 'local');
        if ($request->hasFile('rdb_certificate') && (! is_string($newCertificatePath) || $newCertificatePath === '')) {
            throw new RuntimeException('The RDB certificate could not be stored securely.');
        }

        $oldCertificatePath = $vendor->registration_certificate_path;
        $oldLogoPath = $vendor->business_logo_path;
        $newLogoPath = null;
        try {
            if ($request->hasFile('business_logo')) {
                $newLogoPath = PublicAsset::store($request->file('business_logo'), 'vendors/logos');
            }

            DB::transaction(function () use ($request, $vendor, $data, $newCertificatePath, $newLogoPath): void {
                $lockedVendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);
                abort_unless($lockedVendor->application_status === 'correction_requested', 409);

                if (! $this->anyFilled($request, self::PAYMENT_FIELDS['bank'])) {
                    foreach (self::PAYMENT_FIELDS['bank'] as $field) {
                        $data[$field] = $lockedVendor->{$field};
                    }
                }
                if (! $this->anyFilled($request, self::PAYMENT_FIELDS['mobile'])) {
                    foreach (self::PAYMENT_FIELDS['mobile'] as $field) {
                        $data[$field] = $lockedVendor->{$field};
                    }
                }

                $attributes = $this->vendorAttributes($data);
                $attributes['status'] = 'pending';
                $attributes['application_status'] = 'pending';
                $attributes['application_submitted_at'] = now();
                if ($newCertificatePath) {
                    $attributes['registration_certificate_path'] = $newCertificatePath;
                }
                if ($newLogoPath) {
                    $attributes['business_logo_path'] = $newLogoPath;
                }
                $lockedVendor->update($attributes);

                $request->user()->update(['name' => $data['contact_person']]);
            });
        } catch (Throwable $exception) {
            if ($newCertificatePath) {
                Storage::disk('local')->delete($newCertificatePath);
            }
            PublicAsset::delete($newLogoPath);
            throw $exception;
        }

        if ($newCertificatePath && $oldCertificatePath) {
            Storage::disk('local')->delete($oldCertificatePath);
        }
        if ($newLogoPath && $oldLogoPath) {
            PublicAsset::delete($oldLogoPath);
        }

        app(MarketplaceNotifier::class)->vendorApplicationResubmitted($vendor->fresh());

        return redirect()->route('vendor.application.submitted')
            ->with('vendor_application_submitted', true);
    }

    private function correctionVendor(User $user): Vendor
    {
        abort_unless($user->hasRole('vendor') && $user->vendor, 403);
        abort_unless($user->vendor->application_status === 'correction_requested', 403);

        return $user->vendor;
    }

    private function markCorrectionNotificationsAsRead(User $user, Vendor $vendor): ?string
    {
        $notifications = $user->unreadNotifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->latest()
            ->get();

        if ($notifications->isEmpty()) {
            return null;
        }

        $notifications->each->markAsRead();

        return $vendor->correction_request;
    }

    private function applicationValidator(Request $request, ?Vendor $vendor)
    {
        $rules = [
            'contact_person' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'tin' => ['required', 'string', 'max:100'],
            'business_phone' => ['required', 'string', 'max:50'],
            'business_email' => ['required', 'email', 'max:255'],
            'business_address' => ['required', 'string', 'max:2000'],
            'rdb_certificate' => [$vendor ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'business_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'mobile_money_provider' => ['nullable', 'in:mtn_momo,airtel_money'],
            'mobile_money_phone' => ['nullable', 'string', 'max:50'],
            'mobile_money_account_holder' => ['nullable', 'string', 'max:255'],
        ];

        if ($vendor) {
            $rules['email'] = ['prohibited'];
            $rules['password'] = ['prohibited'];
            $rules['password_confirmation'] = ['prohibited'];
        } else {
            $rules['email'] = [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                'unique:customers,email',
            ];
            $rules['password'] = ['required', 'string', Password::default(), 'confirmed'];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request, $vendor): void {
            $bankEntered = $this->anyFilled($request, self::PAYMENT_FIELDS['bank']);
            $mobileEntered = $this->anyFilled($request, self::PAYMENT_FIELDS['mobile']);
            $bankComplete = $this->allFilled($request, self::PAYMENT_FIELDS['bank']);
            $mobileComplete = $this->allFilled($request, self::PAYMENT_FIELDS['mobile']);

            if ($bankEntered && ! $bankComplete) {
                $this->addMissingFieldErrors($validator, $request, self::PAYMENT_FIELDS['bank']);
            }
            if ($mobileEntered && ! $mobileComplete) {
                $this->addMissingFieldErrors($validator, $request, self::PAYMENT_FIELDS['mobile']);
            }

            $existingPaymentMethod = $vendor
                && ($this->allPresent($vendor, self::PAYMENT_FIELDS['bank']) || $this->allPresent($vendor, self::PAYMENT_FIELDS['mobile']));
            if (! $bankComplete && ! $mobileComplete && ! ($vendor && ! $bankEntered && ! $mobileEntered && $existingPaymentMethod)) {
                $validator->errors()->add('bank_name', 'Provide complete bank details, complete Mobile Money details, or both.');
            }
        });

        return $validator;
    }

    private function vendorAttributes(array $data): array
    {
        return [
            'business_name' => $data['business_name'],
            'legal_name' => $data['business_name'],
            'contact_person' => $data['contact_person'],
            'tin' => $data['tin'],
            'phone' => $data['business_phone'],
            'email' => $data['business_email'],
            'business_email' => $data['business_email'],
            'business_address' => $data['business_address'],
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_holder' => $data['bank_account_holder'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'mobile_money_provider' => $data['mobile_money_provider'] ?? null,
            'mobile_money_phone' => $data['mobile_money_phone'] ?? null,
            'mobile_money_account_holder' => $data['mobile_money_account_holder'] ?? null,
        ];
    }

    private function anyFilled(Request $request, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                return true;
            }
        }

        return false;
    }

    private function allFilled(Request $request, array $fields): bool
    {
        foreach ($fields as $field) {
            if (! $request->filled($field)) {
                return false;
            }
        }

        return true;
    }

    private function allPresent(Vendor $vendor, array $fields): bool
    {
        foreach ($fields as $field) {
            if (blank($vendor->{$field})) {
                return false;
            }
        }

        return true;
    }

    private function addMissingFieldErrors($validator, Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (! $request->filled($field)) {
                $validator->errors()->add($field, 'Complete all fields for this payment method.');
            }
        }
    }

    private function safeOldInput(Request $request): array
    {
        return $request->except([
            'password',
            'password_confirmation',
            'bank_account_number',
            'bank_account_holder',
            'mobile_money_phone',
            'mobile_money_account_holder',
            'rdb_certificate',
            'business_logo',
        ]);
    }
}

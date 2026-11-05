<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user() && $request->user()->hasPermission('admin.access'), 403);

            return $next($request);
        });
    }

    /**
     * Settings overview page displaying grouped settings rows.
     */
    public function index(): View
    {
        return view('settings.index');
    }

    /**
     * Dedicated Profile & Password settings page.
     */
    public function profile(): View
    {
        return view('settings.profile', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update administrator profile details (name, email, phone, photo).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo')) {
            $user->deleteProfilePhoto();
        } elseif ($request->hasFile('photo')) {
            $user->updateProfilePhoto($request->file('photo'));
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->save();

        AuditLogger::log($user, 'profile.updated', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        return back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update administrator password with current password verification and secure hashing.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided current password does not match our records.',
            ])->withInput();
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        $request->session()->put('password_hash_web', $user->getAuthPassword());

        AuditLogger::log($user, 'password.updated', $user);

        return back()->with('success', 'Your password has been changed securely.');
    }

    /**
     * Dedicated Business Profile settings page.
     */
    public function businessProfile(): View
    {
        return view('settings.business-profile', [
            'appName' => config('app.name', 'TRESCA Hub'),
            'appUrl' => config('app.url', 'https://trescahub.rw'),
            'supportEmail' => 'info@trescahub.com',
            'contactPhone' => '+250 786 908 916',
            'operatingLocation' => 'Kigali, Rwanda',
            'organization' => 'TRESCA GROUP',
        ]);
    }

    /**
     * Dedicated Shopping & Orders configuration page.
     */
    public function shoppingOrders(): View
    {
        return view('settings.shopping-orders', [
            'orderTransitions' => config('marketplace.order_transitions', []),
            'requestTransitions' => config('marketplace.request_transitions', []),
        ]);
    }

    /**
     * Dedicated Payments & Pricing configuration page.
     */
    public function paymentsPricing(): View
    {
        return view('settings.payments-pricing', [
            'currency' => 'RWF',
            'bankInstructions' => config('marketplace.bank_instructions', 'Pay on delivery, or contact TRESCA Hub for bank transfer details.'),
            'paymentMethods' => [
                'mtn_mobile_money' => 'MTN Mobile Money',
                'airtel_money' => 'Airtel Money',
                'bank_transfer' => 'Bank Transfer / Pay on Delivery',
            ],
        ]);
    }

    /**
     * Dedicated Delivery configuration page.
     */
    public function delivery(): View
    {
        return view('settings.delivery', [
            'deliveryMethods' => config('marketplace.delivery_methods', [
                'in_house' => 'In-house Delivery',
                'third_party_courier' => 'Third-party Courier',
                'customer_collection' => 'Customer Collection',
            ]),
        ]);
    }

    /**
     * Dedicated Products & Stock configuration page.
     */
    public function productsStock(): View
    {
        return view('settings.products-stock', [
            'criticalFields' => config('marketplace.critical_product_fields', []),
        ]);
    }

    /**
     * Dedicated Vendors & Partners configuration page.
     */
    public function vendorsPartners(): View
    {
        return view('settings.vendors-partners', [
            'vendorItemTransitions' => config('marketplace.vendor_item_transitions', []),
        ]);
    }

    /**
     * Dedicated Team & Permissions configuration page.
     */
    public function teamPermissions(): RedirectResponse
    {
        return $this->redirectToUnavailableSettings('Team & Permissions');
    }

    /**
     * Dedicated Messages & Alerts notification configuration page.
     */
    public function messagesAlerts(): RedirectResponse
    {
        return $this->redirectToUnavailableSettings('Messages & Alerts');
    }

    /**
     * Dedicated Website Appearance configuration page.
     */
    public function websiteAppearance(): RedirectResponse
    {
        return $this->redirectToUnavailableSettings('Website Appearance');
    }

    /**
     * Dedicated Security & System configuration page.
     */
    public function securitySystem(): RedirectResponse
    {
        return $this->redirectToUnavailableSettings('Security & System');
    }

    private function redirectToUnavailableSettings(string $section): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.index')
            ->withErrors(['settings' => "{$section} settings are not available yet."]);
    }
}

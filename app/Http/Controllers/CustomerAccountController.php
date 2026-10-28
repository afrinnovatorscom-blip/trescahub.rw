<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductRequest;
use App\Models\Servicerequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerAccountController extends Controller
{
    public function profile(Request $request)
    {
        $customer = $this->customerFor($request)->load('addresses');

        return view('front.pages.account-profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = $this->customerFor($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('customers')->ignore($customer->id),
                Rule::unique('users')->ignore($customer->user_id),
            ],
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $customer->update($data);

        return back()->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:customers'],
            'password' => ['required', 'string', \Illuminate\Validation\Rules\Password::default(), 'confirmed'],
        ]);
        $this->customerFor($request)->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return back()->with('success', 'Your password has been updated.');
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validatedAddress($request);
        $customer = $this->customerFor($request);

        DB::transaction(function () use ($customer, $data, $request) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $makeDefault = $request->boolean('is_default') || ! $customer->addresses()->exists();

            if ($makeDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            $customer->addresses()->create([
                ...$data,
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('success', 'Address saved.');
    }

    public function updateAddress(Request $request, int $address)
    {
        $customer = $this->customerFor($request);
        $data = $this->validatedAddress($request);

        DB::transaction(function () use ($customer, $address, $data, $request) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $customerAddress = $customer->addresses()->lockForUpdate()->findOrFail($address);
            $makeDefault = $request->boolean('is_default') || $customerAddress->is_default;

            if ($makeDefault) {
                $customer->addresses()->where('id', '!=', $customerAddress->id)->update(['is_default' => false]);
            }

            $customerAddress->update([
                ...$data,
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('success', 'Address updated.');
    }

    public function destroyAddress(Request $request, int $address)
    {
        $customer = $this->customerFor($request);

        DB::transaction(function () use ($customer, $address) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $customerAddress = $customer->addresses()->lockForUpdate()->findOrFail($address);
            $wasDefault = $customerAddress->is_default;
            $customerAddress->delete();

            if ($wasDefault) {
                $customer->addresses()->latest('id')->limit(1)->update(['is_default' => true]);
            }
        });

        return back()->with('success', 'Address removed.');
    }

    public function requests(Request $request)
    {
        $customer = $this->customerFor($request);

        return view('front.pages.account-requests', [
            'productRequests' => ProductRequest::query()->where('customer_id', $customer->id)->latest()->limit(50)->get(),
            'serviceRequests' => Servicerequest::query()->where('customer_id', $customer->id)->latest()->limit(50)->get(),
        ]);
    }

    private function customerFor(Request $request): Customer
    {
        $customer = Auth::guard('customers')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }

    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:50'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'address_line' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }
}

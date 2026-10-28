@extends('front.layouts.guest')

@section('title', 'My Account')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 900px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">My Account</h1>
                <p class="text-muted mb-0">Manage your personal information, delivery address, and account security.</p>
            </div>
        </div>

        @include('marketplace.partials.flash')

        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Personal Details</h2>
                <form method="POST" action="{{ route('account.profile.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full Name</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" value="{{ old('phone', auth()->user()->phone) }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <button class="btn text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-1">Address</h2>
                    <p class="text-muted small mb-4">Your default address is used to prefill checkout and product requests.</p>

                    @php
                        $defaultAddress = $customer->addresses->firstWhere('is_default', true) ?? $customer->addresses->first();
                        $selectedAddressId = old('saved_address_id', $defaultAddress?->id ?? 'new');
                        $selectedAddress = $customer->addresses->firstWhere('id', (int) $selectedAddressId);
                        $hasAddressInput = old('address_line') !== null;
                    @endphp
                    <form
                        method="POST"
                        action="{{ $selectedAddress ? route('account.addresses.update', $selectedAddress) : route('account.addresses.store') }}"
                        data-address-form
                        data-store-url="{{ route('account.addresses.store') }}"
                        class="row g-3"
                    >
                        @csrf
                        @if($selectedAddress)
                            @method('PUT')
                        @endif
                        @if($customer->addresses->isNotEmpty())
                            <div class="col-12">
                                <label class="form-label" for="saved_address_id">Saved address</label>
                                <select class="form-select" id="saved_address_id" name="saved_address_id" data-address-select>
                                    @foreach($customer->addresses as $address)
                                        <option
                                            value="{{ $address->id }}"
                                            data-update-url="{{ route('account.addresses.update', $address) }}"
                                            data-delete-url="{{ route('account.addresses.destroy', $address) }}"
                                            data-address="{{ json_encode($address->only(['label', 'recipient_name', 'recipient_phone', 'recipient_email', 'address_line', 'district', 'city', 'delivery_instructions', 'is_default'])) }}"
                                            @selected((string) $selectedAddressId === (string) $address->id)
                                        >{{ $address->label }}{{ $address->is_default ? ' (Default)' : '' }}</option>
                                    @endforeach
                                    <option value="new" @selected((string) $selectedAddressId === 'new')>Add a new address</option>
                                </select>
                                <p class="form-text mb-0">Choose a saved address to update it, or select “Add a new address” to save another.</p>
                            </div>
                        @else
                            <input type="hidden" name="saved_address_id" value="new">
                        @endif
                        <div class="col-md-6">
                            <label class="form-label" for="address_label">Address label</label>
                            <input class="form-control @error('label') is-invalid @enderror" id="address_label" name="label" value="{{ old('label', $selectedAddress?->label ?? 'Home') }}" required>
                            @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="recipient_name">Recipient name</label>
                            <input class="form-control @error('recipient_name') is-invalid @enderror" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $selectedAddress?->recipient_name ?? auth()->user()->name) }}" required>
                            @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="recipient_phone">Recipient phone</label>
                            <input class="form-control @error('recipient_phone') is-invalid @enderror" id="recipient_phone" name="recipient_phone" type="tel" value="{{ old('recipient_phone', $selectedAddress?->recipient_phone ?? auth()->user()->phone) }}" required>
                            @error('recipient_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="recipient_email">Recipient email</label>
                            <input class="form-control @error('recipient_email') is-invalid @enderror" id="recipient_email" name="recipient_email" type="email" value="{{ old('recipient_email', $selectedAddress?->recipient_email ?? auth()->user()->email) }}">
                            @error('recipient_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address_line">Address</label>
                            <textarea class="form-control @error('address_line') is-invalid @enderror" id="address_line" name="address_line" rows="2" required>{{ old('address_line', $selectedAddress?->address_line) }}</textarea>
                            @error('address_line')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="district">District</label>
                            <input class="form-control @error('district') is-invalid @enderror" id="district" name="district" value="{{ old('district', $selectedAddress?->district) }}">
                            @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="city">City</label>
                            <input class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $selectedAddress?->city) }}">
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="delivery_instructions">Delivery instructions</label>
                            <textarea class="form-control @error('delivery_instructions') is-invalid @enderror" id="delivery_instructions" name="delivery_instructions" rows="2">{{ old('delivery_instructions', $selectedAddress?->delivery_instructions) }}</textarea>
                            @error('delivery_instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-check-label">
                                <input class="form-check-input me-1" type="checkbox" name="is_default" value="1" @checked(old('is_default', $selectedAddress?->is_default ?? $customer->addresses->isEmpty()))>
                                Make this my default address
                            </label>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button class="btn text-white" style="background:#16a34a;border-color:#16a34a;" type="submit" data-address-save>Save Address</button>
                            @if($customer->addresses->isNotEmpty())
                                <button class="btn btn-outline-danger" type="submit" name="intent" value="delete" data-address-delete @disabled(!$selectedAddress)>Remove Address</button>
                            @endif
                        </div>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const form = document.querySelector('[data-address-form]');
                            const selector = form?.querySelector('[data-address-select]');
                            if (!form || !selector) return;

                            const fields = ['label', 'recipient_name', 'recipient_phone', 'recipient_email', 'address_line', 'district', 'city', 'delivery_instructions'];
                            const deleteButton = form.querySelector('[data-address-delete]');
                            const hasAddressInput = @json($hasAddressInput);

                            const updateFormMode = (restoreSubmittedValues = false) => {
                                const option = selector.selectedOptions[0];
                                const isNewAddress = option.value === 'new';
                                form.action = isNewAddress ? form.dataset.storeUrl : option.dataset.updateUrl;

                                let methodOverride = form.querySelector('input[name="_method"]');
                                if (isNewAddress) {
                                    methodOverride?.remove();
                                } else if (!methodOverride) {
                                    methodOverride = document.createElement('input');
                                    methodOverride.type = 'hidden';
                                    methodOverride.name = '_method';
                                    form.append(methodOverride);
                                }
                                if (methodOverride) methodOverride.value = 'PUT';

                                if (deleteButton) deleteButton.disabled = isNewAddress;
                                if (!restoreSubmittedValues && !isNewAddress) {
                                    const address = JSON.parse(option.dataset.address);
                                    for (const field of fields) {
                                        const input = form.elements.namedItem(field);
                                        if (input) input.value = address[field] ?? '';
                                    }
                                    form.elements.namedItem('is_default').checked = Boolean(address.is_default);
                                } else if (!restoreSubmittedValues && isNewAddress) {
                                    for (const field of fields) {
                                        const input = form.elements.namedItem(field);
                                        if (input) input.value = field === 'label' ? 'Home' : (field === 'recipient_name' ? @json(auth()->user()->name) : (field === 'recipient_phone' ? @json(auth()->user()->phone) : (field === 'recipient_email' ? @json(auth()->user()->email) : '')));
                                    }
                                    form.elements.namedItem('is_default').checked = selector.options.length === 1;
                                }

                            };

                            selector.addEventListener('change', () => updateFormMode());
                            form.addEventListener('submit', (event) => {
                                if (event.submitter?.name !== 'intent' || event.submitter.value !== 'delete') return;
                                if (!window.confirm('Remove this saved address?')) {
                                    event.preventDefault();
                                    return;
                                }
                                form.action = selector.selectedOptions[0].dataset.deleteUrl;
                                let methodOverride = form.querySelector('input[name="_method"]');
                                if (!methodOverride) {
                                    methodOverride = document.createElement('input');
                                    methodOverride.type = 'hidden';
                                    methodOverride.name = '_method';
                                    form.append(methodOverride);
                                }
                                methodOverride.value = 'DELETE';
                            });

                            updateFormMode(hasAddressInput);
                        });
                    </script>
                </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Change Password</h2>
                <form method="POST" action="{{ route('account.password.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                        @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">New Password</label>
                        <input class="form-control @error('password', 'updatePassword') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                        @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Confirm New Password</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="col-12">
                        <button class="btn text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Update Password</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection

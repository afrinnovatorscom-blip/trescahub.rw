@php
    $isEdit = $partner !== null;
    $formAction = $isEdit ? route('partners.update', $partner) : route('partners.store');
    $logoUrl = $isEdit && $partner->logo_path ? asset('assets/'.ltrim($partner->logo_path, '/')) : null;
@endphp

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="partner-form">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <section class="partner-form-section" aria-labelledby="partner-details-heading">
        <div class="partner-section-heading">
            <div class="partner-section-icon"><i class="bi bi-building" aria-hidden="true"></i></div>
            <div>
                <h2 id="partner-details-heading">Partner details</h2>
                <p>Basic information shown on your website.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label for="partner-logo" class="form-label">Partner logo <span class="text-muted fw-normal">(optional)</span></label>
                <div class="partner-logo-field">
                    <div class="partner-logo-preview">
                        <img
                            id="partner-logo-preview"
                            src="{{ $logoUrl ?? '' }}"
                            alt="Partner logo preview"
                            @if(!$logoUrl) hidden @endif
                        >
                        <span id="partner-logo-placeholder" @if($logoUrl) hidden @endif>
                            <i class="bi bi-image" aria-hidden="true"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <input
                            id="partner-logo"
                            name="logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            class="form-control partner-control @error('logo') is-invalid @enderror"
                            aria-describedby="partner-logo-help @error('logo') partner-logo-error @enderror"
                        >
                        <div id="partner-logo-help" class="partner-help">JPG, PNG, WebP or GIF. Maximum file size: 2 MB.</div>
                        @error('logo')
                            <div id="partner-logo-error" class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-12">
                <label for="partner-name" class="form-label">Partner name <span class="text-danger">*</span></label>
                <input
                    id="partner-name"
                    name="title"
                    type="text"
                    value="{{ old('title', $partner->title ?? '') }}"
                    maxlength="255"
                    required
                    class="form-control partner-control @error('title') is-invalid @enderror"
                    placeholder="e.g. Acme Group"
                    autocomplete="organization"
                >
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="partner-description" class="form-label">Short description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea
                    id="partner-description"
                    name="description"
                    rows="3"
                    maxlength="500"
                    class="form-control partner-control @error('description') is-invalid @enderror"
                    placeholder="A brief introduction to this partner"
                >{{ old('description', $partner->description ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="partner-website" class="form-label">Website URL <span class="text-muted fw-normal">(optional)</span></label>
                <input
                    id="partner-website"
                    name="website_url"
                    type="url"
                    value="{{ old('website_url', $partner->website_url ?? '') }}"
                    maxlength="2048"
                    class="form-control partner-control @error('website_url') is-invalid @enderror"
                    placeholder="https://example.com"
                    autocomplete="url"
                >
                @error('website_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <section class="partner-form-section" aria-labelledby="partner-publishing-heading">
        <div class="partner-section-heading">
            <div class="partner-section-icon"><i class="bi bi-globe2" aria-hidden="true"></i></div>
            <div>
                <h2 id="partner-publishing-heading">Publishing</h2>
                <p>Control whether this partner is visible to website visitors.</p>
            </div>
        </div>

        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6">
                <div class="partner-visibility-control">
                    <div>
                        <label for="partner-show-on-website" class="form-label mb-1">Show on website</label>
                        <div class="partner-help">Only active partners with this enabled appear publicly.</div>
                    </div>
                    <input type="hidden" name="show_on_website" value="0">
                    <div class="form-check form-switch mb-0">
                        <input
                            id="partner-show-on-website"
                            name="show_on_website"
                            type="checkbox"
                            value="1"
                            role="switch"
                            class="form-check-input @error('show_on_website') is-invalid @enderror"
                            @checked((bool) old('show_on_website', $partner->show_on_website ?? false))
                        >
                    </div>
                </div>
                @error('show_on_website')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="partner-status" class="form-label">Status <span class="text-danger">*</span></label>
                <select
                    id="partner-status"
                    name="status"
                    required
                    class="form-select partner-control @error('status') is-invalid @enderror"
                >
                    <option value="active" @selected(old('status', $partner->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $partner->status ?? 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <div class="partner-form-actions">
        <a href="{{ route('partners.index') }}" class="btn partner-btn-secondary">Cancel</a>
        <button type="submit" class="btn partner-btn-primary">
            <i class="bi bi-check2" aria-hidden="true"></i>
            {{ $isEdit ? 'Save changes' : 'Save partner' }}
        </button>
    </div>
</form>

<script>
    document.getElementById('partner-logo')?.addEventListener('change', function (event) {
        const file = event.target.files?.[0];
        const preview = document.getElementById('partner-logo-preview');
        const placeholder = document.getElementById('partner-logo-placeholder');

        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        placeholder.hidden = true;
    });
</script>

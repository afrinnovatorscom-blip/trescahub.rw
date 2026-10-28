<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'business_name',
        'legal_name',
        'reference_number',
        'email',
        'phone',
        'status',
        'district',
        'operating_location',
        'address_notes',
        'internal_notes',
        'public_branding_approved',
        'business_logo_path',
        'website_url',
        'show_as_partner',
        'partner_description',
        'display_order',
        'partner_display_status',
    ];

    protected $casts = [
        'public_branding_approved' => 'boolean',
        'show_as_partner' => 'boolean',
        'display_order' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ProductRequest::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopePublishedOnWebsite(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('show_as_partner', true)
            ->where('partner_display_status', 'published')
            ->where('public_branding_approved', true)
            ->whereNotNull('business_name')
            ->whereRaw('TRIM(business_name) <> ?', ['']);
    }

    public function hasWebsitePublicationEnabled(): bool
    {
        return $this->partner_display_status === 'published';
    }

    public function publicationBlockReason(): ?string
    {
        if (! $this->isActive()) {
            return 'Only active vendors can be published on the website.';
        }

        if ($this->show_as_partner !== true) {
            return 'This vendor is not marked for public partner display.';
        }

        if (trim((string) $this->business_name) === '') {
            return 'A business name is required before this vendor can be published.';
        }

        if ($this->public_branding_approved !== true) {
            return $this->public_branding_approved === false
                ? 'This vendor cannot be published because branding approval is not complete.'
                : 'This vendor cannot be published because its branding approval status is unknown.';
        }

        return null;
    }

    public function websitePublicationLabel(): string
    {
        if (! $this->hasWebsitePublicationEnabled()) {
            return 'Not published';
        }

        $blockReason = $this->publicationBlockReason();

        return $blockReason ? 'Blocked: '.$blockReason : 'Published';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Approval',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'inactive' => 'Inactive',
            null, '' => 'Unknown',
            default => 'Unknown ('.$this->status.')',
        };
    }

    public function partnerStatusLabel(): string
    {
        if ($this->show_as_partner === null) {
            return 'Unknown';
        }

        if (! $this->show_as_partner) {
            return 'Not a Partner';
        }

        if ($this->status !== 'active') {
            return 'Blocked by vendor status: '.$this->statusLabel();
        }

        return match ($this->partner_display_status) {
            'draft' => 'Draft',
            'published' => match ($this->public_branding_approved) {
                true => 'Published',
                false => 'Publication blocked: branding not approved',
                null => 'Publication blocked: branding status unknown',
            },
            null, '' => 'Unknown',
            default => 'Unknown ('.$this->partner_display_status.')',
        };
    }

    public function brandingStatusLabel(): string
    {
        return match ($this->public_branding_approved) {
            true => 'Approved',
            false => 'Not approved',
            null => 'Unknown',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'vendor-status-active',
            'pending' => 'vendor-status-review',
            'suspended', 'inactive' => 'vendor-status-suspended',
            default => 'vendor-status-unknown',
        };
    }
}

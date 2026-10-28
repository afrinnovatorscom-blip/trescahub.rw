<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'vendor_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'vendor_id',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected ?Collection $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')->withTimestamps();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function productRequests(): HasMany
    {
        return $this->hasMany(ProductRequest::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(Servicerequest::class);
    }

    public function assignRole(string $slug): void
    {
        $role = Role::query()->where('slug', $slug)->first();

        if (! $role) {
            return;
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->forgetPermissionCache();
    }

    public function forgetPermissionCache(): void
    {
        $this->permissionCache = null;
        $this->unsetRelation('roles');
        $this->unsetRelation('directPermissions');
    }

    public function hasRole(string $slug): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(fn (Role $role) => $role->slug === $slug);
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->hasRole('super-administrator')) {
            return true;
        }

        return $this->permissionSlugs()->contains($slug);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    public function permissionSlugs(): Collection
    {
        if ($this->permissionCache instanceof Collection) {
            return $this->permissionCache;
        }

        $this->loadMissing('roles.permissions', 'directPermissions');

        $this->permissionCache = $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->pluck('slug')
            ->merge($this->directPermissions->pluck('slug'))
            ->unique()
            ->values();

        return $this->permissionCache;
    }

    public function isVendorOnly(): bool
    {
        return $this->hasRole('vendor') && ! $this->hasPermission('admin.access');
    }

    public function roleLabel(): string
    {
        $this->loadMissing('roles');

        $label = $this->roles->pluck('name')->join(', ');

        return $label !== '' ? $label : 'User';
    }
}

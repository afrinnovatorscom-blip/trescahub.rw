<?php

namespace App\Policies;

use App\Models\ShopProduct;
use App\Models\User;

class ShopProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.access') && $user->hasPermission('products.view');
    }

    public function view(User $user, ShopProduct $product): bool
    {
        return $user->hasPermission('admin.access') && $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.access') && $user->hasPermission('products.create');
    }

    public function update(User $user, ShopProduct $product): bool
    {
        return $user->hasPermission('admin.access') && $user->hasPermission('products.edit');
    }

    public function delete(User $user, ShopProduct $product): bool
    {
        return $user->hasPermission('admin.access') && $user->hasPermission('products.delete');
    }
}

<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionCatalog
{
    public static function definitions(): array
    {
        return [
            'admin.access' => 'Open the internal administration area',
            'cms.manage' => 'Manage services, pages, partners, and other existing content',
            'products.view' => 'View products',
            'products.create' => 'Create products',
            'products.edit' => 'Edit products',
            'products.delete' => 'Delete products',
            'products.submit' => 'Submit products for review',
            'products.approve' => 'Approve or reject products',
            'products.publish' => 'Publish or unpublish products',
            'products.assign_vendor' => 'Assign a vendor to a product',
            'inventory.view' => 'View inventory quantities',
            'inventory.manage' => 'Adjust inventory',
            'locations.view' => 'View internal stock locations',
            'locations.manage' => 'Manage internal stock locations',
            'vendors.view' => 'View vendors',
            'vendors.create' => 'Register vendors',
            'vendors.manage' => 'Edit, activate, or suspend vendors',
            'vendors.view_contacts' => 'View vendor phone numbers and email addresses',
            'requests.view_all' => 'View every product request',
            'requests.view_assigned' => 'View assigned product requests',
            'requests.allocate' => 'Allocate a product request',
            'requests.reassign' => 'Reassign a product request',
            'orders.view_all' => 'View every order',
            'orders.view_assigned' => 'View assigned orders',
            'orders.manage' => 'Update orders and fulfillment',
            'deliveries.manage' => 'Manage deliveries',
            'reports.view' => 'View operational reports',
            'finance.view' => 'View acquisition costs, margins, and revenue',
            'users.manage' => 'Manage internal users',
            'roles.manage' => 'Manage roles',
            'permissions.manage' => 'Change which permissions a role grants',
            'audit_logs.view' => 'View the audit log',
        ];
    }

    public static function roles(): array
    {
        $all = array_keys(self::definitions());

        return [
            'super-administrator' => [
                'name' => 'Super Administrator',
                'description' => 'Full system control.',
                'permissions' => $all,
            ],
            'administrator' => [
                'name' => 'Administrator',
                'description' => 'Operates the marketplace. Role and permission catalogs stay with the super administrator.',
                'permissions' => array_values(array_diff($all, ['roles.manage', 'permissions.manage'])),
            ],
            'operations-manager' => [
                'name' => 'Operations Manager',
                'description' => 'Allocates requests and monitors fulfillment. Contacts and costs are separate grants.',
                'permissions' => [
                    'admin.access', 'products.view', 'inventory.view', 'locations.view', 'vendors.view',
                    'requests.view_all', 'requests.view_assigned', 'requests.allocate', 'requests.reassign',
                    'orders.view_all', 'orders.view_assigned', 'orders.manage', 'deliveries.manage', 'reports.view',
                ],
            ],
            'product-manager' => [
                'name' => 'Product Manager',
                'description' => 'Maintains the catalog and submits listings. Approval is a separate grant.',
                'permissions' => [
                    'admin.access', 'products.view', 'products.create', 'products.edit', 'products.submit', 'inventory.view',
                ],
            ],
            'request-officer' => [
                'name' => 'Request and Order Officer',
                'description' => 'Reviews requests, allocates vendors, and updates operational order status.',
                'permissions' => [
                    'admin.access', 'products.view', 'locations.view', 'vendors.view',
                    'requests.view_all', 'requests.view_assigned', 'requests.allocate', 'requests.reassign',
                    'orders.view_all', 'orders.view_assigned', 'orders.manage',
                ],
            ],
            'inventory-officer' => [
                'name' => 'Inventory Officer',
                'description' => 'Manages stock quantities and storage locations.',
                'permissions' => [
                    'admin.access', 'products.view', 'inventory.view', 'inventory.manage', 'locations.view', 'locations.manage',
                ],
            ],
            'delivery-officer' => [
                'name' => 'Delivery Officer',
                'description' => 'Updates assigned deliveries and customer-facing milestones.',
                'permissions' => [
                    'admin.access', 'orders.view_assigned', 'deliveries.manage', 'locations.view',
                ],
            ],
            'vendor' => [
                'name' => 'Vendor',
                'description' => 'Works only on products, requests, and orders assigned to their account.',
                'permissions' => [
                    'products.view', 'products.create', 'products.edit', 'products.submit',
                    'requests.view_assigned', 'orders.view_assigned', 'inventory.view', 'inventory.manage',
                ],
            ],
            'customer' => [
                'name' => 'Customer',
                'description' => 'Buys and tracks their own orders.',
                'permissions' => [],
            ],
        ];
    }

    public static function sync(): void
    {
        foreach (self::definitions() as $slug => $name) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'group' => strstr($slug, '.', true) ?: 'general']
            );
        }

        foreach (self::roles() as $slug => $config) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'is_system' => true,
                ]
            );

            if ($slug === 'super-administrator' || $role->permissions()->count() === 0) {
                $ids = Permission::query()->whereIn('slug', $config['permissions'])->pluck('id');
                $role->permissions()->sync($ids);
            }
        }

        self::ensureSuperAdmin();
    }

    public static function ensureSuperAdmin(): void
    {
        $roleId = Role::query()->where('slug', 'super-administrator')->value('id');

        if (! $roleId) {
            return;
        }

        $already = DB::table('role_user')->where('role_id', $roleId)->exists();

        if ($already) {
            return;
        }

        $firstUserId = User::query()->orderBy('id')->value('id');

        if (! $firstUserId) {
            return;
        }

        DB::table('role_user')->insert([
            'role_id' => $roleId,
            'user_id' => $firstUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function backfillProducts(): void
    {
        DB::table('shop_products')->whereNull('approval_status')->update([
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);

        DB::table('shop_products')
            ->whereNull('publication_status')
            ->update(['publication_status' => 'published']);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class MarketplaceUserSeeder extends Seeder
{
    /**
     * Local demo accounts. Password for every account is "password".
     * Do not run this seeder on production.
     */
    public function run(): void
    {
        PermissionCatalog::sync();

        $vendor = Vendor::query()->updateOrCreate(
            ['reference_number' => 'VND-SEED-001'],
            [
                'business_name' => 'Kigali Supplies',
                'legal_name' => 'Kigali Supplies Ltd',
                'email' => 'vendor@example.com',
                'phone' => '0788001001',
                'status' => 'active',
                'district' => 'Gasabo',
                'operating_location' => 'Internal warehouse',
                'internal_notes' => 'Seeded vendor account for local development.',
            ]
        );

        $accounts = [
            ['super-administrator', 'Super Administrator', 'super@example.com', '0788001000', null],
            ['administrator', 'Administrator', 'admin@example.com', '0788001002', null],
            ['operations-manager', 'Operations Manager', 'operations@example.com', '0788001003', null],
            ['product-manager', 'Product Manager', 'catalog@example.com', '0788001004', null],
            ['request-officer', 'Request Officer', 'requests@example.com', '0788001005', null],
            ['inventory-officer', 'Inventory Officer', 'inventory@example.com', '0788001006', null],
            ['delivery-officer', 'Delivery Officer', 'delivery@example.com', '0788001007', null],
            ['vendor', 'Vendor User', 'vendor@example.com', '0788001001', $vendor->id],
            ['customer', 'Customer', 'customer@example.com', '0788001008', null],
        ];

        foreach ($accounts as [$slug, $name, $email, $phone, $vendorId]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'password' => 'password',
                    'vendor_id' => $vendorId,
                    'email_verified_at' => now(),
                ]
            );

            if ($user->vendor_id !== $vendorId) {
                $user->vendor_id = $vendorId;
                $user->save();
            }

            $roleId = Role::query()->where('slug', $slug)->value('id');
            $user->roles()->sync([$roleId]);
            $user->forgetPermissionCache();
        }

        // Shop (storefront) customer. Customers sign in through the public site and live in
        // their own "customers" table, separate from the staff accounts above.
        $customer = Customer::query()->firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Demo Customer',
                'phone' => '0788001008',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        if (! $customer->addresses()->exists()) {
            $customer->addresses()->create([
                'label' => 'Home',
                'recipient_name' => 'Demo Customer',
                'recipient_phone' => '0788001008',
                'recipient_email' => 'customer@example.com',
                'address_line' => 'KG 11 Ave',
                'city' => 'Kigali',
                'district' => 'Gasabo',
                'is_default' => true,
            ]);
        }
    }
}

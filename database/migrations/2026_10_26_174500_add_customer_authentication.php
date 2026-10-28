<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('customer_id');
        });

        Schema::table('product_requests', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('customer_id');
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('customer_id');
        });

        $eligibleUserIds = DB::table('users')
            ->whereNull('users.vendor_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->where('roles.slug', 'customer');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->where('roles.slug', '!=', 'customer');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('customers')
                    ->whereColumn('customers.user_id', 'users.id');
            })
            ->pluck('users.id');

        foreach ($eligibleUserIds as $userId) {
            DB::table('customers')->insertOrIgnore([
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $legacyCustomers = DB::table('customers')
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->whereNotNull('customers.user_id')
            ->whereNull('users.vendor_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->where('roles.slug', 'customer');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->where('roles.slug', '!=', 'customer');
            })
            ->select([
                'customers.id as customer_id',
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.password',
                'users.phone',
                'users.email_verified_at',
            ])
            ->get();

        foreach ($legacyCustomers as $legacyCustomer) {
            DB::table('customers')->where('id', $legacyCustomer->customer_id)->update([
                'name' => $legacyCustomer->name,
                'email' => $legacyCustomer->email,
                'password' => $legacyCustomer->password,
                'phone' => $legacyCustomer->phone,
                'email_verified_at' => $legacyCustomer->email_verified_at,
                'remember_token' => null,
                'updated_at' => now(),
            ]);

            DB::table('orders')->where('user_id', $legacyCustomer->user_id)->whereNull('customer_id')
                ->update(['customer_id' => $legacyCustomer->customer_id]);
            DB::table('product_requests')->where('user_id', $legacyCustomer->user_id)->whereNull('customer_id')
                ->update(['customer_id' => $legacyCustomer->customer_id]);
            DB::table('service_requests')->where('user_id', $legacyCustomer->user_id)->whereNull('customer_id')
                ->update(['customer_id' => $legacyCustomer->customer_id]);

            DB::table('users')->where('id', $legacyCustomer->user_id)->update([
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'updated_at' => now(),
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email');
        });

    }

    public function down(): void
    {
        foreach (['orders', 'product_requests', 'service_requests'] as $table) {
            if (DB::table($table)->whereNotNull('customer_id')->exists()) {
                throw new RuntimeException('Cannot remove customer ownership while customer-linked records exist.');
            }
        }

        if (DB::table('customers')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Cannot remove customer authentication while standalone customer accounts exist.');
        }

        foreach (DB::table('customers')->whereNotNull('user_id')->whereNotNull('password')->get(['user_id', 'password']) as $customer) {
            DB::table('users')->where('id', $customer->user_id)->update([
                'password' => $customer->password,
                'remember_token' => null,
                'updated_at' => now(),
            ]);
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('product_requests', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn([
                'name',
                'email',
                'password',
                'phone',
                'email_verified_at',
                'remember_token',
            ]);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::dropIfExists('customer_password_reset_tokens');
    }
};

<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['permission_id', 'user_id']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('legal_name')->nullable();
            $table->string('reference_number')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('pending');
            $table->string('district')->nullable();
            $table->string('operating_location')->nullable();
            $table->text('address_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('public_branding_approved')->default(false);
            $table->timestamps();
            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('vendor_id')->nullable()->after('phone')->constrained('vendors')->nullOnDelete();
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('sku')->nullable()->unique();
            $table->string('approval_status')->nullable();
            $table->string('publication_status')->nullable();
            $table->string('revision_status')->nullable();
            $table->json('pending_payload')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('acquisition_cost', 12, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->boolean('tracks_inventory')->default(false);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('product_type')->nullable();
            $table->string('condition')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->index('approval_status');
            $table->index('publication_status');
        });

        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->string('warehouse_name');
            $table->string('district')->nullable();
            $table->string('description')->nullable();
            $table->string('shelf_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('product_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('product_location_id')->nullable()->constrained('product_locations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->integer('quantity_delta');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->unsignedInteger('reserved_before')->default(0);
            $table->unsignedInteger('reserved_after')->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('delivery_address');
            $table->text('delivery_notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('fulfillment_status')->default('pending_confirmation');
            $table->string('customer_status_message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivery_estimate_at')->nullable();
            $table->timestamps();
            $table->index('fulfillment_status');
            $table->index('payment_status');
            $table->index('phone');
        });

        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shop_product_id')->nullable()->constrained('shop_products')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_location_id')->nullable()->constrained('product_locations')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->text('instructions')->nullable();
            $table->string('delivery_location')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('submitted');
            $table->string('customer_message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_request_id')->constrained('product_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->string('customer_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_product_id')->nullable()->constrained('shop_products')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('product_location_id')->nullable()->constrained('product_locations')->nullOnDelete();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->string('fulfillment_status')->default('pending_confirmation');
            $table->string('assignment_status')->default('unassigned');
            $table->string('inventory_state')->default('none');
            $table->text('fulfillment_instructions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->index('vendor_id');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->string('customer_message')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('product_location_id')->nullable()->constrained('product_locations')->nullOnDelete();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('assigned');
            $table->text('fulfillment_instructions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('customer_message')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('attempted_at');
            $table->string('result');
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('changes')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        PermissionCatalog::backfillProducts();
        PermissionCatalog::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('delivery_attempts');
        Schema::dropIfExists('delivery_tasks');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('request_status_histories');
        Schema::dropIfExists('product_requests');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_approval_histories');
        Schema::dropIfExists('product_locations');

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'sku',
                'approval_status',
                'publication_status',
                'revision_status',
                'pending_payload',
                'acquisition_cost',
                'stock_quantity',
                'reserved_quantity',
                'tracks_inventory',
                'low_stock_threshold',
                'product_type',
                'condition',
                'internal_notes',
                'approved_at',
                'submitted_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn('phone');
        });

        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

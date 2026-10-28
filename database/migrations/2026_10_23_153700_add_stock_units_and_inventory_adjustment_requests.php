<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('stock_unit', 32)->nullable()->after('stock_quantity');
        });

        Schema::create('inventory_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 20);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before');
            $table->integer('quantity_delta')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('reason');
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_requests');

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn('stock_unit');
        });
    }
};

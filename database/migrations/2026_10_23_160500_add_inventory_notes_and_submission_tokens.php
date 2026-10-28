<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->text('inventory_notes')->nullable();
        });

        Schema::create('inventory_adjustment_submissions', function (Blueprint $table) {
            $table->uuid('token')->primary();
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_submissions');

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn('inventory_notes');
        });
    }
};

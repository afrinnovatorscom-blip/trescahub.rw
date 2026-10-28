<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->boolean('discount_enabled')->default(false);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->boolean('is_finished')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['discount_enabled', 'original_price', 'is_finished']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('slug')->unique();
            $table->enum('status', ['in_stock', 'out_of_stock'])->default('in_stock');
            $table->boolean('is_popular')->default(false);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('working_days')->default(1);
            $table->json('features')->nullable();
            $table->json('specifications')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('images')->nullable();
            $table->unsignedBigInteger('shop_category_id');
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('shop_category_id')
                  ->references('id')->on('shop_categories')
                  ->cascadeOnDelete();

            $table->foreign('subcategory_id')
                  ->references('id')->on('shop_categories')
                  ->nullOnDelete();

            // Indexes for Performance
            $table->index('slug');                      // For route model binding lookups
            $table->index('status');                    // For status filtering
            $table->index('is_popular');                // For popular products filtering
            $table->index('shop_category_id');          // For category queries
            $table->index('subcategory_id');            // For subcategory queries
            $table->index('created_at');                // For sorting by latest
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['product_name', 'description']); // MySQL full-text search
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variations')) {
            Schema::create('product_variations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete();
                $table->string('feature_label', 255);
                $table->string('variation_value', 255);
                $table->decimal('price', 12, 2);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->index(['shop_product_id', 'is_default'], 'product_variations_product_default_index');
            });
        }

        Schema::table('order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_items', 'product_variation_id')) {
                $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            }
            if (! Schema::hasColumn('order_items', 'variation_label')) {
                $table->string('variation_label')->nullable();
            }
            if (! Schema::hasColumn('order_items', 'variation_value')) {
                $table->string('variation_value')->nullable();
            }
        });

        Schema::table('product_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_requests', 'product_variation_id')) {
                $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            }
            if (! Schema::hasColumn('product_requests', 'variation_label')) {
                $table->string('variation_label')->nullable();
            }
            if (! Schema::hasColumn('product_requests', 'variation_value')) {
                $table->string('variation_value')->nullable();
            }
            if (! Schema::hasColumn('product_requests', 'variation_price')) {
                $table->decimal('variation_price', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('product_requests')) {
            Schema::table('product_requests', function (Blueprint $table): void {
                if (Schema::hasColumn('product_requests', 'product_variation_id')) {
                    $table->dropConstrainedForeignId('product_variation_id');
                }

                foreach (['variation_label', 'variation_value', 'variation_price'] as $column) {
                    if (Schema::hasColumn('product_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table): void {
                if (Schema::hasColumn('order_items', 'product_variation_id')) {
                    $table->dropConstrainedForeignId('product_variation_id');
                }

                foreach (['variation_label', 'variation_value'] as $column) {
                    if (Schema::hasColumn('order_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('product_variations');
    }
};

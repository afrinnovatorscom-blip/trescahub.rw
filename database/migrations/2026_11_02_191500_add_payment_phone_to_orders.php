<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'payment_phone')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('payment_phone', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'payment_phone')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('payment_phone');
            });
        }
    }
};

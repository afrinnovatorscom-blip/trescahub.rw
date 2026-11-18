<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->string('reference', 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->string('reference', 50)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->date('appointment_date')->nullable()->after('preferred_time');
            $table->string('appointment_time', 20)->nullable()->after('appointment_date');
        });
    }

    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn(['appointment_date', 'appointment_time']);
        });
    }
};

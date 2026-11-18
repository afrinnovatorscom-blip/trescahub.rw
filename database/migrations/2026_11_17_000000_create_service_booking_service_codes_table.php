<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_booking_service_codes', function (Blueprint $table) {
            $table->foreignId('service_id')->primary()->constrained()->cascadeOnDelete();
            $table->char('service_code', 1)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_booking_service_codes');
    }
};

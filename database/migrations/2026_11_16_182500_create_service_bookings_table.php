<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('submission_key');
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();
            $table->date('preferred_date');
            $table->string('preferred_time', 20);
            $table->text('requirements')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed'])->default('pending');
            $table->enum('payment_status', ['paid', 'partially_paid', 'not_paid'])->default('not_paid');
            $table->timestamps();

            $table->unique(['service_id', 'submission_key']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_bookings');
    }
};

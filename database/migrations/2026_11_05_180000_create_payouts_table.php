<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money sent from the IntouchPay account to a mobile money number (B2C).
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // staff member who sent it
            $table->string('mobile_phone', 20);
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->string('status')->default('pending')->index(); // pending, successful, failed
            $table->string('request_transaction_id')->unique();
            $table->string('reference_id')->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('message')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};

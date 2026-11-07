<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per mobile money attempt. An order can have several (e.g. a retry after a failed prompt).
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('intouchpay');
            $table->string('method'); // mtn_mobile_money, airtel_money
            $table->string('phone', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('RWF');
            $table->string('status')->default('pending')->index(); // pending, completed, failed
            $table->string('request_transaction_id')->unique();
            $table->string('transaction_id')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            // Nullable: a callback can arrive for a transaction we cannot match
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('request_transaction_id')->nullable()->index();
            $table->string('type', 30)->index(); // request, callback, status_check
            $table->string('response_code', 20)->nullable();
            $table->string('status')->nullable();
            $table->string('message')->nullable();
            $table->json('payload')->nullable();  // what was sent to us / by us
            $table->json('response')->nullable(); // what came back / what we answered
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('payment_transactions');
    }
};

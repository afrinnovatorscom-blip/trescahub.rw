<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Home');
            $table->string('recipient_name');
            $table->string('recipient_phone', 50);
            $table->string('recipient_email')->nullable();
            $table->text('address_line');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['customer_id', 'is_default']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};

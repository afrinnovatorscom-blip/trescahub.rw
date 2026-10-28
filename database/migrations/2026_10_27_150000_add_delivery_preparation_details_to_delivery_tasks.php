<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->string('delivery_method', 80)->nullable();
            $table->string('courier_name', 150)->nullable();
            $table->string('delivery_phone', 40)->nullable();
            $table->string('vehicle_registration', 40)->nullable();
            $table->date('expected_delivery_at')->nullable();
            $table->string('tracking_reference', 100)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('dispatch_proof_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_method',
                'courier_name',
                'delivery_phone',
                'vehicle_registration',
                'expected_delivery_at',
                'tracking_reference',
                'delivery_notes',
                'dispatch_proof_path',
            ]);
        });
    }
};

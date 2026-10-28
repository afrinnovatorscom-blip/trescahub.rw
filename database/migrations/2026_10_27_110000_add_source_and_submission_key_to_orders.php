<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source')->nullable()->after('reference');
            $table->uuid('submission_key')->nullable()->unique()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['submission_key']);
            $table->dropColumn(['source', 'submission_key']);
        });
    }
};

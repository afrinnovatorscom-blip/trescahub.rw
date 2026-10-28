<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('business_logo_path')->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->boolean('show_as_partner')->default(false);
            $table->text('partner_description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('partner_display_status')->default('draft');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'business_logo_path',
                'website_url',
                'show_as_partner',
                'partner_description',
                'display_order',
                'partner_display_status',
            ]);
        });
    }
};

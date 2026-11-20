<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('application_status')->nullable()->index();
            $table->string('contact_person')->nullable();
            $table->string('tin')->nullable();
            $table->string('business_email')->nullable();
            $table->text('business_address')->nullable();
            $table->string('registration_certificate_path')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_account_holder')->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('mobile_money_provider')->nullable();
            $table->text('mobile_money_phone')->nullable();
            $table->text('mobile_money_account_holder')->nullable();
            $table->timestamp('application_submitted_at')->nullable();
            $table->foreignId('application_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('correction_request')->nullable();
            $table->timestamp('correction_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['application_reviewed_by']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['application_status']);
            $table->dropColumn([
                'application_status',
                'contact_person',
                'tin',
                'business_email',
                'business_address',
                'registration_certificate_path',
                'bank_name',
                'bank_account_holder',
                'bank_account_number',
                'mobile_money_provider',
                'mobile_money_phone',
                'mobile_money_account_holder',
                'application_submitted_at',
                'application_reviewed_by',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'correction_request',
                'correction_requested_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_bookings')
            ->whereIn('payment_status', ['paid', 'partially_paid'])
            ->whereNotNull('payment_amount_received')
            ->where('payment_amount_received', '>', 0)
            ->update(['payment_recorded' => true]);
    }

    public function down(): void {}
};

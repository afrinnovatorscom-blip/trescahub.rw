<?php

use App\Services\ServiceBookingReferenceGenerator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ServiceBookingReferenceGenerator::class)->refreshExistingReferences();
    }

    public function down(): void
    {
        // Existing references are intentionally not reverted to the legacy format.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missingColumns = [];

        if (! Schema::hasColumn('partners', 'logo_path')) {
            $missingColumns[] = 'logo_path';
        }

        if (! Schema::hasColumn('partners', 'website_url')) {
            $missingColumns[] = 'website_url';
        }

        if (! Schema::hasColumn('partners', 'show_on_website')) {
            $missingColumns[] = 'show_on_website';
        }

        if (! Schema::hasColumn('partners', 'status')) {
            $missingColumns[] = 'status';
        }

        if ($missingColumns !== []) {
            Schema::table('partners', function (Blueprint $table) use ($missingColumns): void {
                foreach ($missingColumns as $column) {
                    if ($column === 'logo_path') {
                        $table->string('logo_path')->nullable();
                    } elseif ($column === 'website_url') {
                        $table->string('website_url', 2048)->nullable();
                    } elseif ($column === 'show_on_website') {
                        $table->boolean('show_on_website')->default(false);
                    } elseif ($column === 'status') {
                        $table->string('status')->default('active');
                    }
                }
            });
        }

        if (! Schema::hasIndex('partners', 'partners_public_visibility_index')) {
            Schema::table('partners', function (Blueprint $table): void {
                $table->index(['status', 'show_on_website'], 'partners_public_visibility_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('partners', 'partners_public_visibility_index')) {
            Schema::table('partners', function (Blueprint $table): void {
                $table->dropIndex('partners_public_visibility_index');
            });
        }

        $existingColumns = array_values(array_filter(
            ['logo_path', 'website_url', 'show_on_website', 'status'],
            fn (string $column): bool => Schema::hasColumn('partners', $column),
        ));

        if ($existingColumns !== []) {
            Schema::table('partners', function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }
};

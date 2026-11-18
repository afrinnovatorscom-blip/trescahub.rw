<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_availability_requests')) {
            Schema::create('vendor_availability_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->string('status')->default('pending_confirmation');
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_availability_request_items')) {
            Schema::create('vendor_availability_request_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('vendor_availability_request_id');
                $table->unsignedBigInteger('order_item_id');
                $table->unsignedInteger('ordered_quantity');
                $table->string('status')->default('pending_confirmation');
                $table->unsignedInteger('available_quantity')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        $this->addMissingForeignKey(
            'vendor_availability_requests',
            'order_id',
            'orders',
            'var_order_fk',
            true,
        );
        $this->addMissingForeignKey(
            'vendor_availability_requests',
            'vendor_id',
            'vendors',
            'var_vendor_fk',
            false,
        );
        $this->addMissingForeignKey(
            'vendor_availability_requests',
            'requested_by',
            'users',
            'var_requester_fk',
            false,
        );
        $this->addMissingForeignKey(
            'vendor_availability_request_items',
            'vendor_availability_request_id',
            'vendor_availability_requests',
            'vari_request_fk',
            true,
        );
        $this->addMissingForeignKey(
            'vendor_availability_request_items',
            'order_item_id',
            'order_items',
            'vari_order_item_fk',
            true,
        );

        $this->addMissingIndex('vendor_availability_requests', ['status'], 'var_status_idx');
        $this->addMissingIndex('vendor_availability_requests', ['order_id', 'vendor_id', 'id'], 'var_order_vendor_idx');
        $this->addMissingIndex(
            'vendor_availability_request_items',
            ['vendor_availability_request_id', 'order_item_id'],
            'vari_request_item_uq',
            true,
        );
    }

    private function addMissingForeignKey(
        string $tableName,
        string $column,
        string $referencedTable,
        string $name,
        bool $cascadeOnDelete,
    ): void {
        $exists = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreignKey): bool => in_array($column, $foreignKey['columns'], true));
        if ($exists) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $name, $cascadeOnDelete): void {
            $foreignKey = $table->foreign($column, $name)->references('id')->on($referencedTable);
            if ($cascadeOnDelete) {
                $foreignKey->cascadeOnDelete();
            } else {
                $foreignKey->nullOnDelete();
            }
        });
    }

    private function addMissingIndex(string $tableName, array $columns, string $name, bool $unique = false): void
    {
        $exists = collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index): bool => $index['columns'] === $columns);
        if ($exists) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $name, $unique): void {
            if ($unique) {
                $table->unique($columns, $name);
            } else {
                $table->index($columns, $name);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_availability_request_items');
        Schema::dropIfExists('vendor_availability_requests');
    }
};

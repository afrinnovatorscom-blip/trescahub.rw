<?php

namespace App\Services;

use App\Exceptions\InventoryException;
use App\Models\InventoryMovement;
use App\Models\OrderItem;
use App\Models\ProductLocation;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(private MarketplaceNotifier $notifier) {}

    public function adjust(ShopProduct $product, int $delta, ?ProductLocation $location, ?User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($product, $delta, $location, $actor, $note) {
            $locked = ShopProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $lockedLocation = $this->lockLocation($locked, $location);

            $newStock = (int) $locked->stock_quantity + $delta;

            if ($newStock < 0 || $newStock < (int) $locked->reserved_quantity) {
                throw new InventoryException('That adjustment would drop stock below zero or below the reserved quantity.');
            }

            if ($lockedLocation) {
                $newLocationStock = (int) $lockedLocation->quantity + $delta;

                if ($newLocationStock < 0 || $newLocationStock < (int) $lockedLocation->reserved_quantity) {
                    throw new InventoryException('That location does not have enough unreserved stock.');
                }

                $lockedLocation->quantity = $newLocationStock;
                $lockedLocation->save();
            }

            $this->write(
                $locked,
                $lockedLocation,
                $actor,
                'adjustment',
                $delta,
                (int) $locked->stock_quantity,
                $newStock,
                (int) $locked->reserved_quantity,
                (int) $locked->reserved_quantity,
                null,
                $note,
            );

            $locked->stock_quantity = $newStock;
            $locked->tracks_inventory = true;
            $locked->save();

            AuditLogger::log($actor, 'inventory.adjusted', $locked, [
                'delta' => $delta,
                'stock_after' => $newStock,
                'location_id' => $lockedLocation?->id,
            ]);

            if ($locked->availableQuantity() <= (int) $locked->low_stock_threshold) {
                $this->notifier->lowStock($locked);
            }
        });
    }

    public function reserve(ShopProduct $product, int $quantity, Model $reference, ?User $actor = null): ?ProductLocation
    {
        if (! $product->tracks_inventory || $quantity < 1) {
            return null;
        }

        return DB::transaction(function () use ($product, $quantity, $reference, $actor) {
            $locked = ShopProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($locked->availableQuantity() < $quantity) {
                throw new InventoryException('Not enough stock is available for '.$locked->product_name.'.');
            }

            $location = ProductLocation::query()
                ->where('shop_product_id', $locked->id)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->first(fn (ProductLocation $row) => $row->availableQuantity() >= $quantity);

            if ($locked->locations()->exists() && ! $location) {
                throw new InventoryException('Not enough stock is available at a storage location for '.$locked->product_name.'.');
            }

            if ($location) {
                $location->reserved_quantity = (int) $location->reserved_quantity + $quantity;
                $location->save();
            }

            $reservedBefore = (int) $locked->reserved_quantity;
            $reservedAfter = $reservedBefore + $quantity;

            $this->write(
                $locked,
                $location,
                $actor,
                'reservation',
                -$quantity,
                (int) $locked->stock_quantity,
                (int) $locked->stock_quantity,
                $reservedBefore,
                $reservedAfter,
                $reference,
                'Reserved for '.$reference->getMorphClass().' #'.$reference->getKey(),
            );

            $locked->reserved_quantity = $reservedAfter;
            $locked->save();

            return $location;
        });
    }

    public function release(OrderItem $item, ?User $actor = null): void
    {
        if ($item->inventory_state !== 'reserved' || ! $item->shop_product_id) {
            return;
        }

        DB::transaction(function () use ($item, $actor) {
            $locked = ShopProduct::query()->whereKey($item->shop_product_id)->lockForUpdate()->first();

            if (! $locked) {
                return;
            }

            $location = $item->product_location_id
                ? ProductLocation::query()->whereKey($item->product_location_id)->lockForUpdate()->first()
                : null;

            $quantity = (int) $item->quantity;
            $reservedBefore = (int) $locked->reserved_quantity;
            $reservedAfter = max(0, $reservedBefore - $quantity);

            if ($location) {
                $location->reserved_quantity = max(0, (int) $location->reserved_quantity - $quantity);
                $location->save();
            }

            $this->write($locked, $location, $actor, 'release', $quantity, (int) $locked->stock_quantity, (int) $locked->stock_quantity, $reservedBefore, $reservedAfter, $item, 'Reservation released');

            $locked->reserved_quantity = $reservedAfter;
            $locked->save();
            $item->inventory_state = 'released';
            $item->save();
        });
    }

    public function commitSale(OrderItem $item, ?User $actor = null): void
    {
        if ($item->inventory_state === 'sold' || $item->inventory_state === 'none' || ! $item->shop_product_id) {
            return;
        }

        DB::transaction(function () use ($item, $actor) {
            $locked = ShopProduct::query()->whereKey($item->shop_product_id)->lockForUpdate()->first();

            if (! $locked || ! $locked->tracks_inventory) {
                $item->inventory_state = 'sold';
                $item->save();

                return;
            }

            $location = $item->product_location_id
                ? ProductLocation::query()->whereKey($item->product_location_id)->lockForUpdate()->first()
                : null;

            $quantity = (int) $item->quantity;

            if ((int) $locked->stock_quantity < $quantity) {
                throw new InventoryException('Stock record is lower than the quantity being fulfilled.');
            }

            $stockBefore = (int) $locked->stock_quantity;
            $reservedBefore = (int) $locked->reserved_quantity;
            $stockAfter = $stockBefore - $quantity;
            $reservedAfter = $item->inventory_state === 'reserved'
                ? max(0, $reservedBefore - $quantity)
                : $reservedBefore;

            if ($location) {
                $location->quantity = max(0, (int) $location->quantity - $quantity);
                if ($item->inventory_state === 'reserved') {
                    $location->reserved_quantity = max(0, (int) $location->reserved_quantity - $quantity);
                }
                $location->save();
            }

            $this->write($locked, $location, $actor, 'sale', -$quantity, $stockBefore, $stockAfter, $reservedBefore, $reservedAfter, $item, 'Fulfilled');

            $locked->stock_quantity = $stockAfter;
            $locked->reserved_quantity = $reservedAfter;
            $locked->save();
            $item->inventory_state = 'sold';
            $item->save();
        });
    }

    public function restock(OrderItem $item, ?User $actor = null): void
    {
        if ($item->inventory_state !== 'sold' || ! $item->shop_product_id) {
            return;
        }

        DB::transaction(function () use ($item, $actor) {
            $locked = ShopProduct::query()->whereKey($item->shop_product_id)->lockForUpdate()->first();

            if (! $locked) {
                return;
            }

            $location = $item->product_location_id
                ? ProductLocation::query()->whereKey($item->product_location_id)->lockForUpdate()->first()
                : null;

            $quantity = (int) $item->quantity;
            $stockBefore = (int) $locked->stock_quantity;
            $stockAfter = $stockBefore + $quantity;

            if ($location) {
                $location->quantity = (int) $location->quantity + $quantity;
                $location->save();
            }

            $this->write($locked, $location, $actor, 'return', $quantity, $stockBefore, $stockAfter, (int) $locked->reserved_quantity, (int) $locked->reserved_quantity, $item, 'Returned to stock');

            $locked->stock_quantity = $stockAfter;
            $locked->tracks_inventory = true;
            $locked->save();
            $item->inventory_state = 'released';
            $item->save();
        });
    }

    private function lockLocation(ShopProduct $product, ?ProductLocation $location): ?ProductLocation
    {
        if (! $location) {
            return null;
        }

        return ProductLocation::query()
            ->whereKey($location->id)
            ->where('shop_product_id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function write(
        ShopProduct $product,
        ?ProductLocation $location,
        ?User $actor,
        string $type,
        int $delta,
        int $stockBefore,
        int $stockAfter,
        int $reservedBefore,
        int $reservedAfter,
        ?Model $reference,
        ?string $note,
    ): void {
        InventoryMovement::query()->create([
            'shop_product_id' => $product->id,
            'product_location_id' => $location?->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'quantity_delta' => $delta,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reserved_before' => $reservedBefore,
            'reserved_after' => $reservedAfter,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
        ]);
    }
}

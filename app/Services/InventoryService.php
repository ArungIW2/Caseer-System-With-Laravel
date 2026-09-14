<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function receive(User $user, Store $store, Product $product, float $quantity, ?float $unitCost = null, ?string $reason = null): Inventory
    {
        return $this->change($user, $store, $product, $quantity, 'in', $unitCost, $reason);
    }

    public function issue(User $user, Store $store, Product $product, float $quantity, ?string $reason = null): Inventory
    {
        return $this->change($user, $store, $product, $quantity, 'out', null, $reason);
    }

    public function adjust(User $user, Store $store, Product $product, float $newQuantity, ?string $reason = null): Inventory
    {
        return DB::transaction(function () use ($user, $store, $product, $newQuantity, $reason): Inventory {
            $inventory = $this->lockInventory($store, $product);
            $before = (float) $inventory->quantity;
            $delta = $newQuantity - $before;

            if ($delta === 0.0) {
                return $inventory;
            }

            $type = $delta > 0 ? 'adjustment_in' : 'adjustment_out';
            $this->apply($inventory, $delta);
            $this->movement($user, $inventory, $type, abs($delta), $reason ?? 'Stock adjustment', $before);

            return $inventory->refresh();
        });
    }

    public function opname(User $user, Store $store, Product $product, float $countedQuantity, ?string $reason = null): Inventory
    {
        return DB::transaction(function () use ($user, $store, $product, $countedQuantity, $reason): Inventory {
            $inventory = $this->lockInventory($store, $product);
            $before = (float) $inventory->quantity;
            $delta = $countedQuantity - $before;

            if ($delta === 0.0) {
                return $inventory;
            }

            $type = $delta > 0 ? 'opname_in' : 'opname_out';
            $this->apply($inventory, $delta);
            $this->movement($user, $inventory, $type, abs($delta), $reason ?? 'Stock opname', $before);

            return $inventory->refresh();
        });
    }

    public function transfer(User $user, Store $from, Store $to, Product $product, float $quantity, ?string $reason = null): void
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_store_id' => 'Tujuan transfer harus berbeda dari toko asal.']);
        }

        DB::transaction(function () use ($user, $from, $to, $product, $quantity, $reason): void {
            $source = $this->lockInventory($from, $product);
            $destination = $this->lockInventory($to, $product);
            $beforeSource = (float) $source->quantity;

            if ($quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah transfer harus lebih dari 0.']);
            }

            if ($beforeSource - (float) $source->reserved_quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Stok tersedia tidak mencukupi untuk transfer.']);
            }

            $this->apply($source, -$quantity);
            $this->movement($user, $source, 'transfer_out', $quantity, $reason ?? 'Stock transfer', $beforeSource);

            $beforeDestination = (float) $destination->quantity;
            $this->apply($destination, $quantity);
            $this->movement($user, $destination, 'transfer_in', $quantity, $reason ?? 'Stock transfer', $beforeDestination);
        });
    }

    private function change(User $user, Store $store, Product $product, float $quantity, string $type, ?float $unitCost, ?string $reason): Inventory
    {
        return DB::transaction(function () use ($user, $store, $product, $quantity, $type, $unitCost, $reason): Inventory {
            if ($quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah harus lebih dari 0.']);
            }

            $inventory = $this->lockInventory($store, $product);
            $before = (float) $inventory->quantity;

            if ($type === 'out' && $before - (float) $inventory->reserved_quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Stok tersedia tidak mencukupi.']);
            }

            $delta = $type === 'in' ? $quantity : -$quantity;
            $this->apply($inventory, $delta);
            $this->movement($user, $inventory, $type, $quantity, $reason, $before, $unitCost);

            return $inventory->refresh();
        });
    }

    private function lockInventory(Store $store, Product $product): Inventory
    {
        return Inventory::query()->firstOrCreate(
            ['store_id' => $store->id, 'product_id' => $product->id],
            ['quantity' => 0, 'reserved_quantity' => 0]
        )->newQuery()->whereKey(function () use ($store, $product) {
            return Inventory::query()->where('store_id', $store->id)->where('product_id', $product->id)->value('id');
        })->lockForUpdate()->firstOrFail();
    }

    private function apply(Inventory $inventory, float $delta): void
    {
        $newQuantity = (float) $inventory->quantity + $delta;
        if ($newQuantity < 0) {
            throw ValidationException::withMessages(['quantity' => 'Stok tidak boleh menjadi negatif.']);
        }

        $inventory->quantity = $newQuantity;
        $inventory->save();
    }

    private function movement(User $user, Inventory $inventory, string $type, float $quantity, ?string $reason, float $before, ?float $unitCost = null): void
    {
        InventoryMovement::create([
            'store_id' => $inventory->store_id,
            'product_id' => $inventory->product_id,
            'user_id' => $user->id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'quantity_before' => $before,
            'quantity_after' => (float) $inventory->quantity,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
    }
}

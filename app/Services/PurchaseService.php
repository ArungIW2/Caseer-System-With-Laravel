<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function create(User $user, Store $store, Supplier $supplier, array $data, array $items): Purchase
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Minimal satu item pembelian harus diisi.']);
        }

        return DB::transaction(function () use ($user, $store, $supplier, $data, $items): Purchase {
            $purchase = Purchase::create([
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'created_by' => $user->id,
                'purchase_number' => $this->number(),
                'purchased_at' => $data['purchased_at'],
                'status' => 'draft',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncTotals($purchase, $items);
            return $purchase->refresh();
        });
    }

    public function receive(User $user, Purchase $purchase, InventoryService $inventoryService): Purchase
    {
        return DB::transaction(function () use ($user, $purchase, $inventoryService): Purchase {
            $purchase = Purchase::query()->with('items.product')->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status !== 'draft') {
                throw ValidationException::withMessages(['purchase' => 'Hanya pembelian draft yang dapat diterima.']);
            }
            $store = $purchase->store()->lockForUpdate()->firstOrFail();
            $items = $purchase->items()->with('product')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['purchase' => 'Pembelian belum memiliki item.']);
            }

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                if (!$product->track_stock) {
                    continue;
                }
                $inventoryService->receive(
                    $user,
                    $store,
                    $product,
                    (float) $item->quantity,
                    (float) $item->unit_cost,
                    'Purchase '.$purchase->purchase_number,
                );
                $product->cost_price = $item->unit_cost;
                $product->save();
            }

            $purchase->status = 'received';
            $purchase->save();
            return $purchase->refresh();
        });
    }

    private function syncTotals(Purchase $purchase, array $items): void
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            $quantity = (float) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];
            $lineBase = $quantity * $unitCost;
            $lineDiscount = (float) ($item['discount'] ?? 0);
            $lineTax = (float) ($item['tax'] ?? 0);
            $lineTotal = max(0, $lineBase - $lineDiscount + $lineTax);

            $purchase->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'discount' => $lineDiscount,
                'tax' => $lineTax,
                'line_total' => $lineTotal,
            ]);
            $subtotal += $lineBase;
            $discount += $lineDiscount;
            $tax += $lineTax;
        }

        $purchase->update([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'grand_total' => max(0, $subtotal - $discount + $tax),
        ]);
    }

    private function number(): string
    {
        do {
            $number = 'PO-'.now()->format('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Purchase::query()->where('purchase_number', $number)->exists());
        return $number;
    }
}

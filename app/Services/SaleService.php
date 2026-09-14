<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function checkout(User $cashier, Store $store, array $items, string $paymentMethod, float $paidAmount, ?string $paymentReference = null, float $discountTotal = 0, float $taxTotal = 0, ?string $notes = null): Sale
    {
        return DB::transaction(function () use ($cashier, $store, $items, $paymentMethod, $paidAmount, $paymentReference, $discountTotal, $taxTotal, $notes): Sale {
            if ($paidAmount < 0 || $discountTotal < 0 || $taxTotal < 0) {
                throw ValidationException::withMessages(['payment' => 'Nilai pembayaran, diskon, dan pajak tidak valid.']);
            }

            /** @var CashSession|null $cashSession */
            $cashSession = CashSession::query()->where('store_id', $store->id)->where('status', 'open')->lockForUpdate()->first();
            if (! $cashSession) {
                throw ValidationException::withMessages(['cash_session' => 'Buka sesi kasir terlebih dahulu sebelum melakukan checkout.']);
            }

            $sale = Sale::create([
                'store_id' => $store->id,
                'cashier_id' => $cashier->id,
                'cash_session_id' => $cashSession->id,
                'invoice_number' => $this->invoiceNumber(),
                'sold_at' => now(),
                'status' => 'completed',
                'notes' => $notes,
            ]);

            $subtotal = 0.0;
            $lineDiscount = 0.0;
            $lineTax = 0.0;
            $inventoryService = app(InventoryService::class);

            foreach ($items as $index => $input) {
                $quantity = (float) ($input['quantity'] ?? 0);
                if ($quantity <= 0) {
                    throw ValidationException::withMessages(["items.{$index}.quantity" => 'Jumlah produk harus lebih dari 0.']);
                }

                /** @var Product $product */
                $product = Product::query()->whereKey($input['product_id'] ?? 0)->lockForUpdate()->first();
                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(["items.{$index}.product_id" => 'Produk tidak tersedia.']);
                }

                $unitPrice = (float) $product->selling_price;
                $discount = max(0, (float) ($input['discount'] ?? 0));
                $tax = max(0, (float) ($input['tax'] ?? 0));
                $base = $unitPrice * $quantity;
                if ($discount > $base) {
                    throw ValidationException::withMessages(["items.{$index}.discount" => 'Diskon item tidak boleh melebihi nilai item.']);
                }
                $lineTotal = max(0, $base - $discount + $tax);

                if ($product->track_stock) {
                    $inventoryService->issue($cashier, $store, $product, $quantity, 'Sale '.$sale->invoice_number);
                }

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $tax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $base;
                $lineDiscount += $discount;
                $lineTax += $tax;
            }

            if ($subtotal <= 0) {
                throw ValidationException::withMessages(['items' => 'Transaksi harus memiliki nilai penjualan.']);
            }

            $discountTotal = $lineDiscount + $discountTotal;
            $taxTotal = $lineTax + $taxTotal;
            $grandTotal = max(0, $subtotal - $discountTotal + $taxTotal);

            if ($paidAmount < $grandTotal) {
                throw ValidationException::withMessages(['paid_amount' => 'Pembayaran kurang dari total transaksi.']);
            }

            $change = $paidAmount - $grandTotal;
            if ($paymentMethod !== 'cash' && abs($change) > 0.009) {
                throw ValidationException::withMessages(['paid_amount' => 'Pembayaran non-tunai harus sama persis dengan total transaksi.']);
            }

            $sale->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidAmount,
                'change_total' => $paymentMethod === 'cash' ? $change : 0,
            ]);

            $sale->payments()->create([
                'method' => $paymentMethod,
                'amount' => $paidAmount,
                'reference' => $paymentReference,
                'paid_at' => now(),
            ]);

            return $sale->load(['items.product', 'payments', 'store', 'cashier', 'cashSession']);
        });
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('YmdHis').'-'.str()->upper(str()->random(6));
        } while (Sale::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}

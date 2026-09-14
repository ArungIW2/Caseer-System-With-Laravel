<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnService
{
    public function create(User $user, Sale $sale, array $items, ?string $reason = null): SaleReturn
    {
        return DB::transaction(function () use ($user, $sale, $items, $reason): SaleReturn {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Hanya transaksi completed yang dapat diretur.']);
            }

            $saleItems = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $returned = DB::table('sale_return_items')
                ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.sale_id', $sale->id)
                ->where('sale_returns.status', 'completed')
                ->select('sale_return_items.sale_item_id', DB::raw('SUM(sale_return_items.quantity) as quantity'))
                ->groupBy('sale_return_items.sale_item_id')
                ->pluck('quantity', 'sale_item_id');

            $normalized = [];
            $refundTotal = 0.0;

            foreach ($items as $item) {
                $saleItemId = (int) ($item['sale_item_id'] ?? 0);
                $quantity = (float) ($item['quantity'] ?? 0);
                $restock = filter_var($item['restock'] ?? true, FILTER_VALIDATE_BOOLEAN);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['items' => 'Jumlah retur harus lebih dari 0.']);
                }
                if (!$saleItems->has($saleItemId)) {
                    throw ValidationException::withMessages(['items' => 'Item retur tidak ditemukan pada transaksi.']);
                }

                $saleItem = $saleItems->get($saleItemId);
                $alreadyReturned = (float) ($returned[$saleItemId] ?? 0);
                $remaining = (float) $saleItem->quantity - $alreadyReturned;

                if ($quantity > $remaining + 0.0005) {
                    throw ValidationException::withMessages(['items' => "Jumlah retur {$saleItem->product_id} melebihi sisa quantity yang dapat diretur."]);
                }

                $unitRefund = (float) $saleItem->line_total / (float) $saleItem->quantity;
                $lineRefund = round($unitRefund * $quantity, 2);
                $refundTotal += $lineRefund;

                $normalized[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $quantity,
                    'refund_unit_price' => round($unitRefund, 2),
                    'refund_total' => $lineRefund,
                    'restock' => $restock,
                ];
            }

            if ($normalized === []) {
                throw ValidationException::withMessages(['items' => 'Minimal satu item harus diretur.']);
            }

            $return = SaleReturn::create([
                'store_id' => $sale->store_id,
                'sale_id' => $sale->id,
                'processed_by' => $user->id,
                'return_number' => $this->number(),
                'returned_at' => now(),
                'status' => 'completed',
                'refund_total' => round($refundTotal, 2),
                'reason' => $reason,
            ]);

            foreach ($normalized as $row) {
                $return->items()->create([
                    'sale_item_id' => $row['sale_item']->id,
                    'quantity' => $row['quantity'],
                    'refund_unit_price' => $row['refund_unit_price'],
                    'refund_total' => $row['refund_total'],
                    'restock' => $row['restock'],
                ]);

                if ($row['restock']) {
                    $product = $row['sale_item']->product()->lockForUpdate()->firstOrFail();
                    if ($product->track_stock) {
                        app(InventoryService::class)->receive(
                            $user,
                            $sale->store,
                            $product,
                            $row['quantity'],
                            null,
                            "Sales Return {$return->return_number}"
                        );
                    }
                }
            }

            return $return->load(['sale.items.product', 'items.saleItem.product', 'store', 'processor']);
        });
    }

    private function number(): string
    {
        do {
            $number = 'RET-' . now()->format('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (SaleReturn::query()->where('return_number', $number)->exists());

        return $number;
    }
}

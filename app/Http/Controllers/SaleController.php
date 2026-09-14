<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService) {}

    public function pos(Request $request): View
    {
        $stores = $this->storesFor($request);
        $selectedStoreId = $this->resolveStoreId($request, $stores);
        $store = $stores->firstWhere('id', $selectedStoreId);

        abort_unless($store, 403, 'Store tidak tersedia untuk akun ini.');

        return view('pos.index', compact('stores', 'store'));
    }

    public function products(Request $request): JsonResponse
    {
        $stores = $this->storesFor($request);
        $store = $stores->firstWhere('id', (int) $request->integer('store_id'));
        abort_unless($store, 403, 'Store tidak tersedia untuk akun ini.');

        $term = trim((string) $request->input('q', ''));
        $products = Product::query()
            ->where('is_active', true)
            ->with(['unit'])
            ->where(function ($query) use ($term): void {
                if ($term !== '') {
                    $query->where('sku', 'ilike', "%{$term}%")
                        ->orWhere('barcode', 'ilike', "%{$term}%")
                        ->orWhere('name', 'ilike', "%{$term}%");
                }
            })
            ->with(['inventories' => fn ($query) => $query->where('store_id', $store->id)])
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json($products->map(function (Product $product): array {
            $inventory = $product->inventories->first();
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'unit' => $product->unit?->symbol,
                'selling_price' => (float) $product->selling_price,
                'stock' => $product->track_stock ? (float) ($inventory?->quantity ?? 0) : null,
                'track_stock' => $product->track_stock,
            ];
        }));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'payment_method' => ['required', 'in:cash,card,qris,transfer'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],
        ]);

        $stores = $this->storesFor($request);
        $store = $stores->firstWhere('id', (int) $validated['store_id']);
        abort_unless($store, 403, 'Store tidak tersedia untuk akun ini.');

        $sale = $this->saleService->checkout(
            $request->user(),
            $store,
            $validated['items'],
            $validated['payment_method'],
            (float) $validated['paid_amount'],
            $validated['payment_reference'] ?? null,
            (float) ($validated['discount_total'] ?? 0),
            (float) ($validated['tax_total'] ?? 0),
            $validated['notes'] ?? null,
        );

        return redirect()->route('sales.show', $sale)->with('success', 'Penjualan berhasil diselesaikan.');
    }

    public function index(Request $request): View
    {
        $stores = $this->storesFor($request);
        $query = Sale::query()->with(['store', 'cashier'])->whereIn('store_id', $stores->pluck('id'));

        if ($request->filled('store_id')) {
            $storeId = (int) $request->input('store_id');
            abort_unless($stores->contains('id', $storeId), 403);
            $query->where('store_id', $storeId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $query->where('invoice_number', 'ilike', '%'.$request->string('q').'%');
        }

        $sales = $query->latest('sold_at')->paginate(15)->withQueryString();
        return view('sales.index', compact('sales', 'stores'));
    }

    public function show(Request $request, Sale $sale): View
    {
        $stores = $this->storesFor($request);
        abort_unless($stores->contains('id', $sale->store_id), 403);
        $sale->load(['items.product.unit', 'payments', 'store', 'cashier']);
        return view('sales.show', compact('sale'));
    }

    private function storesFor(Request $request)
    {
        $user = $request->user();
        return in_array($user->role, ['super_admin', 'owner'], true)
            ? Store::query()->where('is_active', true)->orderBy('name')->get()
            : Store::query()->whereKey($user->store_id)->where('is_active', true)->get();
    }

    private function resolveStoreId(Request $request, $stores): int
    {
        if ($request->filled('store_id')) {
            $id = (int) $request->input('store_id');
            abort_unless($stores->contains('id', $id), 403);
            return $id;
        }

        return (int) $stores->first()->id;
    }
}

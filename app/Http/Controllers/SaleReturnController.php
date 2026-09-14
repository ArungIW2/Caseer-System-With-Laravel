<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Store;
use App\Services\SaleReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    public function __construct(private readonly SaleReturnService $service) {}

    public function index(Request $request): View
    {
        $stores = $this->storesFor($request);
        $query = SaleReturn::query()
            ->with(['sale', 'store', 'processor'])
            ->whereIn('store_id', $stores->pluck('id'))
            ->latest('returned_at');

        if ($request->filled('store_id')) {
            $storeId = (int) $request->input('store_id');
            $this->authorizeStore($request, $storeId);
            $query->where('store_id', $storeId);
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('return_number', 'ilike', "%{$search}%")
                    ->orWhereHas('sale', fn ($sale) => $sale->where('invoice_number', 'ilike', "%{$search}%"));
            });
        }

        return view('sale-returns.index', [
            'returns' => $query->paginate(15)->withQueryString(),
            'stores' => $stores,
        ]);
    }

    public function create(Request $request, Sale $sale): View
    {
        $this->authorizeStore($request, $sale->store_id);
        abort_unless($sale->status === 'completed', 422, 'Transaksi belum berstatus completed.');

        $sale->load(['store', 'cashier', 'items.product.unit', 'returns.items']);
        $returned = $sale->returns
            ->where('status', 'completed')
            ->flatMap(fn ($return) => $return->items)
            ->groupBy('sale_item_id')
            ->map(fn ($items) => $items->sum(fn ($item) => (float) $item->quantity));

        return view('sale-returns.form', compact('sale', 'returned'));
    }

    public function store(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorizeStore($request, $sale->store_id);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.restock' => ['nullable', 'boolean'],
        ]);

        try {
            $return = $this->service->create($request->user(), $sale, $validated['items'], $validated['reason'] ?? null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        return redirect()->route('sale-returns.show', $return)->with('success', "Retur {$return->return_number} berhasil diproses. Refund: Rp " . number_format((float) $return->refund_total, 2, ',', '.'));
    }

    public function show(Request $request, SaleReturn $saleReturn): View
    {
        $this->authorizeStore($request, $saleReturn->store_id);
        $saleReturn->load(['sale', 'store', 'processor', 'items.saleItem.product.unit']);
        return view('sale-returns.show', compact('saleReturn'));
    }

    private function storesFor(Request $request)
    {
        $user = $request->user();
        if ($user->hasRole(['super_admin', 'owner'])) {
            return Store::query()->where('is_active', true)->orderBy('name')->get();
        }
        return Store::query()->whereKey($user->store_id)->where('is_active', true)->get();
    }

    private function authorizeStore(Request $request, int $storeId): void
    {
        $user = $request->user();
        if ($user->hasRole(['super_admin', 'owner'])) return;
        abort_unless((int) $user->store_id === $storeId, 403);
    }
}

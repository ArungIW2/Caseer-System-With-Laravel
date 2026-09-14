<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $store = $this->resolveStore($request->integer('store_id') ?: null);
        $query = Inventory::query()->with('product')->where('store_id', $store->id);
        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'products.minimum_stock')
                ->join('products', 'products.id', '=', 'inventories.product_id')
                ->select('inventories.*');
        }
        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('product', fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('sku', 'ilike', "%{$search}%")->orWhere('barcode', 'ilike', "%{$search}%"));
        }

        return view('inventory.index', [
            'store' => $store,
            'stores' => $this->availableStores(),
            'inventories' => $query->orderBy('id')->paginate(20)->withQueryString(),
            'lowStockCount' => Inventory::query()->where('store_id', $store->id)->whereHas('product', fn ($q) => $q->whereColumn('inventories.quantity', '<=', 'products.minimum_stock'))->count(),
        ]);
    }

    public function movements(Request $request): View
    {
        $store = $this->resolveStore($request->integer('store_id') ?: null);
        $query = InventoryMovement::query()->with(['product', 'user'])->where('store_id', $store->id);
        if ($request->input('movement_type')) {
            $query->where('movement_type', $request->string('movement_type'));
        }

        return view('inventory.movements', [
            'store' => $store,
            'stores' => $this->availableStores(),
            'movements' => $query->latest('occurred_at')->paginate(30)->withQueryString(),
        ]);
    }

    public function create(string $action): View
    {
        abort_unless(in_array($action, ['receive', 'issue', 'adjust', 'opname', 'transfer'], true), 404);
        return view('inventory.form', [
            'action' => $action,
            'stores' => $this->availableStores(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'selling_price']),
        ]);
    }

    public function store(Request $request, string $action): RedirectResponse
    {
        abort_unless(in_array($action, ['receive', 'issue', 'adjust', 'opname', 'transfer'], true), 404);

        $rules = [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'numeric', 'gt:0'],
            'new_quantity' => ['nullable', 'numeric', 'min:0'],
            'counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'to_store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ];
        $data = $request->validate($rules);
        $store = $this->resolveStore((int) $data['store_id']);
        $product = Product::findOrFail($data['product_id']);

        if ($action === 'receive') {
            $this->inventoryService->receive($request->user(), $store, $product, (float) $data['quantity'], isset($data['unit_cost']) ? (float) $data['unit_cost'] : null, $data['reason'] ?? null);
        } elseif ($action === 'issue') {
            $this->inventoryService->issue($request->user(), $store, $product, (float) $data['quantity'], $data['reason'] ?? null);
        } elseif ($action === 'adjust') {
            $this->inventoryService->adjust($request->user(), $store, $product, (float) $data['new_quantity'], $data['reason'] ?? null);
        } elseif ($action === 'opname') {
            $this->inventoryService->opname($request->user(), $store, $product, (float) $data['counted_quantity'], $data['reason'] ?? null);
        } else {
            $toStore = $this->resolveStore((int) $data['to_store_id']);
            $this->inventoryService->transfer($request->user(), $store, $toStore, $product, (float) $data['quantity'], $data['reason'] ?? null);
        }

        return redirect()->route('inventory.index', ['store_id' => $store->id])->with('success', 'Perubahan stok berhasil disimpan.');
    }

    private function availableStores()
    {
        $user = request()->user();
        return $user->hasRole(['super_admin', 'owner'])
            ? Store::query()->where('is_active', true)->orderBy('name')->get()
            : Store::query()->whereKey($user->store_id)->where('is_active', true)->get();
    }

    private function resolveStore(?int $storeId): Store
    {
        $user = request()->user();
        if ($user->hasRole(['super_admin', 'owner'])) {
            return Store::query()->where('is_active', true)->findOrFail($storeId ?? Store::query()->where('is_active', true)->value('id'));
        }

        abort_unless($user->store_id && (!$storeId || $user->store_id === $storeId), 403);
        return Store::query()->whereKey($user->store_id)->where('is_active', true)->firstOrFail();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService, private InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $stores = $this->storesFor($request->user());
        $storeId = $request->integer('store_id') ?: $request->user()->store_id;
        if ($request->user()->hasRole(['super_admin', 'owner']) && !$storeId) $storeId = $stores->first()?->id;

        $purchases = Purchase::query()->with(['supplier', 'store', 'creator'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('purchased_at')->paginate(15)->withQueryString();

        return view('purchases.index', compact('purchases', 'stores', 'storeId'));
    }

    public function create(Request $request)
    {
        $stores = $this->storesFor($request->user());
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        return view('purchases.form', compact('stores', 'suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'items.*.discount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $store = $this->storesFor($request->user())->firstWhere('id', (int) $validated['store_id']);
        abort_unless($store, 403);
        $supplier = Supplier::query()->findOrFail($validated['supplier_id']);
        $purchase = $this->purchaseService->create($request->user(), $store, $supplier, $validated, $validated['items']);
        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase draft berhasil dibuat.');
    }

    public function show(Purchase $purchase, Request $request)
    {
        $this->authorizeStore($request, $purchase);
        $purchase->load(['items.product', 'supplier', 'store', 'creator']);
        return view('purchases.show', compact('purchase'));
    }

    public function receive(Purchase $purchase, Request $request)
    {
        $this->authorizeStore($request, $purchase);
        $this->purchaseService->receive($request->user(), $purchase, $this->inventoryService);
        return back()->with('success', 'Pembelian diterima dan stok berhasil ditambahkan.');
    }

    private function storesFor($user)
    {
        return Store::query()->where('is_active', true)
            ->when(!$user->hasRole(['super_admin', 'owner']), fn ($q) => $q->whereKey($user->store_id))
            ->orderBy('name')->get();
    }

    private function authorizeStore(Request $request, Purchase $purchase): void
    {
        if (!$request->user()->hasRole(['super_admin', 'owner']) && $purchase->store_id !== $request->user()->store_id) abort(403);
    }
}

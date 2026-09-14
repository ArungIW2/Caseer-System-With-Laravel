<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $stores = $this->storesFor($request);
        $storeIds = $stores->pluck('id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $sales = Sale::query()->whereIn('store_id', $storeIds)->where('status', 'completed')->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if ($request->filled('store_id')) {
            abort_unless($stores->contains('id', (int) $request->store_id), 403);
            $sales->where('store_id', (int) $request->store_id);
        }

        $purchaseQuery = Purchase::query()->whereIn('store_id', $storeIds)->where('status', 'received')->whereBetween('purchased_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if ($request->filled('store_id')) $purchaseQuery->where('store_id', (int) $request->store_id);

        $salesTotal = (clone $sales)->sum('grand_total');
        $transactionCount = (clone $sales)->count();
        $purchaseTotal = (clone $purchaseQuery)->sum('grand_total');
        $cashTotal = (clone $sales)->whereHas('payments', fn ($q) => $q->where('method', 'cash'))->with('payments')->get()->sum(fn ($sale) => (float) $sale->payments->where('method', 'cash')->sum('amount') - (float) $sale->change_total);
        $paymentBreakdown = Payment::query()->whereHas('sale', function ($q) use ($storeIds, $from, $to, $request) {
            $q->whereIn('store_id', $storeIds)->where('status', 'completed')->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59']);
            if ($request->filled('store_id')) $q->where('store_id', (int) $request->store_id);
        })->selectRaw('method, SUM(amount) as total')->groupBy('method')->orderByDesc('total')->get();

        $topProducts = \App\Models\SaleItem::query()
            ->with('product')
            ->whereHas('sale', function ($q) use ($storeIds, $from, $to, $request) {
                $q->whereIn('store_id', $storeIds)->where('status', 'completed')->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59']);
                if ($request->filled('store_id')) $q->where('store_id', (int) $request->store_id);
            })
            ->selectRaw('product_id, SUM(quantity) as quantity, SUM(line_total) as total')
            ->groupBy('product_id')->orderByDesc('quantity')->limit(10)->get();

        $lowStockQuery = Inventory::query()->with('product')->whereIn('store_id', $storeIds)->whereColumn('quantity', '<=', 'products.minimum_stock')->join('products', 'products.id', '=', 'inventories.product_id')->select('inventories.*');
        if ($request->filled('store_id')) $lowStockQuery->where('store_id', (int) $request->store_id);
        $lowStockCount = $lowStockQuery->count();

        return view('reports.index', compact('stores', 'from', 'to', 'salesTotal', 'transactionCount', 'purchaseTotal', 'cashTotal', 'paymentBreakdown', 'topProducts', 'lowStockCount'));
    }

    private function storesFor(Request $request)
    {
        $user = $request->user();
        return in_array($user->role, ['super_admin', 'owner'], true)
            ? Store::query()->where('is_active', true)->orderBy('name')->get()
            : Store::query()->whereKey($user->store_id)->where('is_active', true)->get();
    }
}

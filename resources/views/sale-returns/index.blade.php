<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sales Returns</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<div class="mx-auto max-w-7xl p-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div><a href="{{ route('dashboard') }}" class="text-sm text-indigo-600">← Dashboard</a><h1 class="mt-2 text-2xl font-bold">Sales Returns & Refunds</h1></div>
    </div>
    @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-700">{{ session('success') }}</div>@endif
    <form class="mb-4 grid gap-3 rounded-xl bg-white p-4 shadow-sm md:grid-cols-4">
        <input name="search" value="{{ request('search') }}" placeholder="Cari nomor retur / invoice" class="rounded-lg border px-3 py-2">
        <select name="store_id" class="rounded-lg border px-3 py-2"><option value="">Semua toko</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected((string)request('store_id') === (string)$store->id)>{{ $store->name }}</option>@endforeach</select>
        <button class="rounded-lg bg-slate-900 px-4 py-2 font-medium text-white">Filter</button>
    </form>
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm"><table class="min-w-full text-sm"><thead class="border-b bg-slate-50 text-left"><tr><th class="p-3">Nomor Retur</th><th class="p-3">Invoice</th><th class="p-3">Toko</th><th class="p-3">Tanggal</th><th class="p-3">Refund</th><th class="p-3">Status</th></tr></thead><tbody class="divide-y">@forelse($returns as $return)<tr><td class="p-3"><a class="font-semibold text-indigo-600" href="{{ route('sale-returns.show', $return) }}">{{ $return->return_number }}</a></td><td class="p-3">{{ $return->sale->invoice_number }}</td><td class="p-3">{{ $return->store->name }}</td><td class="p-3">{{ $return->returned_at->format('d/m/Y H:i') }}</td><td class="p-3">Rp {{ number_format((float)$return->refund_total, 2, ',', '.') }}</td><td class="p-3">{{ $return->status }}</td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada retur.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $returns->links() }}</div>
</div></body></html>

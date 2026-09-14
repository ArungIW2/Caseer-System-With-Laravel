<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Retur {{ $sale->invoice_number }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<div class="mx-auto max-w-6xl p-6">
    <a href="{{ route('sales.show', $sale) }}" class="text-sm text-indigo-600">← Kembali ke transaksi</a>
    <h1 class="mt-2 text-2xl font-bold">Retur Penjualan</h1><p class="text-slate-500">Invoice {{ $sale->invoice_number }} · {{ $sale->store->name }}</p>
    @if($errors->any())<div class="my-4 rounded-lg bg-red-50 p-3 text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('sale-returns.store', $sale) }}" class="mt-6 space-y-4">@csrf
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm"><table class="min-w-full text-sm"><thead class="border-b bg-slate-50 text-left"><tr><th class="p-3">Produk</th><th class="p-3">Terjual</th><th class="p-3">Sudah Diretur</th><th class="p-3">Qty Retur</th><th class="p-3">Restock</th><th class="p-3">Refund</th></tr></thead><tbody class="divide-y">
        @foreach($sale->items as $item)
            @php $already=(float)($returned[$item->id] ?? 0); $remaining=max(0,(float)$item->quantity-$already); $unitRefund=(float)$item->line_total/(float)$item->quantity; @endphp
            <tr><td class="p-3"><div class="font-semibold">{{ $item->product->name }}</div><div class="text-xs text-slate-500">{{ $item->product->sku }}</div></td><td class="p-3">{{ number_format((float)$item->quantity,3,',','.') }}</td><td class="p-3">{{ number_format($already,3,',','.') }}</td><td class="p-3"><input type="hidden" name="items[{{ $item->id }}][sale_item_id]" value="{{ $item->id }}"><input name="items[{{ $item->id }}][quantity]" type="number" min="0" max="{{ $remaining }}" step="0.001" value="0" data-refund-unit="{{ $unitRefund }}" class="qty w-28 rounded-lg border px-2 py-1"></td><td class="p-3"><label class="inline-flex items-center gap-2"><input type="checkbox" name="items[{{ $item->id }}][restock]" value="1" checked> Ya</label></td><td class="p-3 font-medium refund">Rp 0,00</td></tr>
        @endforeach
        </tbody></table></div>
        <div class="rounded-xl bg-white p-4 shadow-sm"><label class="block text-sm font-medium">Alasan retur</label><textarea name="reason" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Contoh: barang rusak / salah produk / pelanggan membatalkan pembelian"></textarea><div class="mt-4 flex items-center justify-between"><span class="font-semibold">Total Refund</span><span id="total" class="text-xl font-bold">Rp 0,00</span></div></div>
        <button class="rounded-lg bg-red-600 px-5 py-2 font-semibold text-white" onclick="return confirm('Proses retur dan pengembalian stok sesuai pilihan?')">Proses Retur</button>
    </form>
</div>
<script>
const format=(n)=>'Rp '+new Intl.NumberFormat('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n);
function recalc(){let total=0;document.querySelectorAll('.qty').forEach(input=>{const value=parseFloat(input.value)||0;const refund=value*(parseFloat(input.dataset.refundUnit)||0);total+=refund;input.closest('tr').querySelector('.refund').textContent=format(refund);});document.getElementById('total').textContent=format(total);}
document.querySelectorAll('.qty').forEach(i=>i.addEventListener('input',recalc));
</script></body></html>

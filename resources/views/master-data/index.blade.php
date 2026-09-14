<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $config['title'] }} - Caseer</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><a href="{{ route('dashboard') }}" class="text-sm text-indigo-600">← Dashboard</a><h1 class="mt-1 text-3xl font-bold">{{ $config['title'] }}</h1></div>
        <a href="{{ route($config['route'].'.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">+ Add {{ $config['singular'] }}</a>
    </div>
    @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    <form method="GET" class="mb-4 flex gap-2"><input name="search" value="{{ $search }}" placeholder="Search..." class="w-full rounded-lg border-slate-300 shadow-sm"><button class="rounded-lg bg-slate-800 px-4 py-2 text-white">Search</button></form>
    <div class="overflow-hidden rounded-xl bg-white shadow ring-1 ring-slate-200">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">ID</th>
                @foreach($config['fields'] as $key => $field)<th class="px-4 py-3 text-left">{{ $field['label'] }}</th>@endforeach
                <th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($items as $item)
                <tr class="hover:bg-slate-50"><td class="px-4 py-3">#{{ $item->id }}</td>
                @foreach($config['fields'] as $key => $field)
                    <td class="px-4 py-3">
                        @if($field['type'] === 'boolean')
                            <span class="rounded-full px-2 py-1 text-xs {{ $item->{$key} ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $item->{$key} ? 'Yes' : 'No' }}</span>
                        @elseif($key === 'category_id'){{ optional($item->category)->name ?? '-' }}
                        @elseif($key === 'brand_id'){{ optional($item->brand)->name ?? '-' }}
                        @elseif($key === 'unit_id'){{ optional($item->unit)->name ?? '-' }}
                        @elseif(in_array($key, ['cost_price','selling_price']))Rp {{ number_format((float)$item->{$key}, 2, ',', '.') }}
                        @else{{ \Illuminate\Support\Str::limit((string)$item->{$key}, 45) }}@endif
                    </td>
                @endforeach
                <td class="px-4 py-3 text-right"><div class="flex justify-end gap-2"><a href="{{ route($config['route'].'.edit', $item->id) }}" class="rounded bg-amber-100 px-3 py-1 text-amber-700">Edit</a><form method="POST" action="{{ route($config['route'].'.destroy', $item->id) }}" onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')<button class="rounded bg-red-100 px-3 py-1 text-red-700">Delete</button></form></div></td>
                </tr>
            @empty<tr><td colspan="{{ count($config['fields']) + 2 }}" class="px-4 py-8 text-center text-slate-500">No data found.</td></tr>@endforelse
            </tbody></table></div>
        <div class="border-t p-4">{{ $items->links() }}</div>
    </div>
</div></body></html>

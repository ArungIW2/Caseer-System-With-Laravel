<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $item ? 'Edit' : 'Add' }} {{ $config['singular'] }} - Caseer</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-50 text-slate-900"><div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
<a href="{{ route($config['route'].'.index') }}" class="text-sm text-indigo-600">← Back to {{ $config['title'] }}</a><h1 class="mt-2 mb-6 text-3xl font-bold">{{ $item ? 'Edit' : 'Add' }} {{ $config['singular'] }}</h1>
@if($errors->any())<div class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $item ? route($config['route'].'.update', $item->id) : route($config['route'].'.store') }}" class="space-y-5 rounded-xl bg-white p-6 shadow ring-1 ring-slate-200">@csrf @if($item) @method('PUT') @endif
<div class="grid gap-5 sm:grid-cols-2">
@foreach($config['fields'] as $key => $field)
    @php($value = old($key, $item?->{$key} ?? ($field['type'] === 'boolean' ? true : '')))
    <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
        @if($field['type'] === 'boolean')
            <label class="flex items-center gap-3 pt-7"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" {{ $value ? 'checked' : '' }} class="rounded border-slate-300"> <span class="text-sm font-medium">{{ $field['label'] }}</span></label>
        @elseif($field['type'] === 'textarea')
            <label class="mb-1 block text-sm font-medium">{{ $field['label'] }}</label><textarea name="{{ $key }}" rows="4" class="w-full rounded-lg border-slate-300">{{ $value }}</textarea>
        @elseif(in_array($field['type'], ['category','brand','unit']))
            @php($options = $field['type'] === 'category' ? ($categories ?? collect()) : ($field['type'] === 'brand' ? ($brands ?? collect()) : ($units ?? collect())))
            <label class="mb-1 block text-sm font-medium">{{ $field['label'] }}@if(!empty($field['required'])) * @endif</label><select name="{{ $key }}" class="w-full rounded-lg border-slate-300"><option value="">-- Select --</option>@foreach($options as $option)<option value="{{ $option->id }}" {{ (string)$value === (string)$option->id ? 'selected' : '' }}>{{ $option->name }}</option>@endforeach</select>
        @else
            <label class="mb-1 block text-sm font-medium">{{ $field['label'] }}@if(!empty($field['required'])) * @endif</label><input type="{{ $field['type'] }}" name="{{ $key }}" value="{{ $value }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif class="w-full rounded-lg border-slate-300">
        @endif
    </div>
@endforeach
</div><div class="flex justify-end gap-3 pt-4"><a href="{{ route($config['route'].'.index') }}" class="rounded-lg border px-4 py-2">Cancel</a><button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white">{{ $item ? 'Update' : 'Create' }}</button></div>
</form></div></body></html>

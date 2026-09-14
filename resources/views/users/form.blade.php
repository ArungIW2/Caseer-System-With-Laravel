@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8"><h1 class="text-2xl font-bold mb-6">{{ $user->exists ? 'Edit User' : 'Tambah User' }}</h1>
@if($errors->any())<div class="mb-4 rounded bg-red-100 p-3 text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-4 bg-white p-6 rounded shadow">@csrf @if($user->exists) @method('PUT') @endif
<label class="block">Nama<input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full border rounded px-3 py-2"></label>
<label class="block">Email<input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full border rounded px-3 py-2"></label>
<label class="block">Password<input type="password" name="password" class="mt-1 w-full border rounded px-3 py-2" {{ $user->exists ? '' : 'required' }}><small class="text-gray-500">Minimal 8 karakter. Kosongkan saat edit jika tidak ingin mengganti.</small></label>
<label class="block">Role<select name="role" class="mt-1 w-full border rounded px-3 py-2">@foreach(['super_admin','owner','manager','cashier','inventory_staff'] as $role)<option value="{{ $role }}" @selected(old('role', $user->role ?: 'cashier') === $role)>{{ $role }}</option>@endforeach</select></label>
<label class="block">Store<select name="store_id" class="mt-1 w-full border rounded px-3 py-2"><option value="">Global / tanpa store</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected((string)old('store_id', $user->store_id) === (string)$store->id)>{{ $store->name }}</option>@endforeach</select></label>
<label class="flex gap-2 items-center"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->exists ? $user->is_active : true))> Aktif</label>
<div class="flex gap-3"><a href="{{ route('admin.users.index') }}" class="px-4 py-2 border rounded">Batal</a><button class="px-4 py-2 rounded bg-gray-900 text-white">Simpan</button></div></form></div>
@endsection

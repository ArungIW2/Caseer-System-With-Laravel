<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Caseer System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-sm font-semibold text-indigo-600">CASEER SYSTEM</p>
                <h1 class="text-xl font-bold">Dashboard</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-xs uppercase text-slate-500">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-8">
        <div class="grid gap-6 md:grid-cols-3">
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Store</p>
                <p class="mt-2 text-lg font-semibold">{{ auth()->user()->store?->name ?? 'Global / belum dipilih' }}</p>
            </section>
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Role</p>
                <p class="mt-2 text-lg font-semibold capitalize">{{ auth()->user()->role }}</p>
            </section>
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Status</p>
                <p class="mt-2 text-lg font-semibold text-emerald-600">Aktif</p>
            </section>
        </div>

        <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Phase 2 — Authentication & Authorization</h2>
            <p class="mt-2 text-slate-600">Login, session authentication, role authorization, dan store-aware user foundation sudah tersedia.</p>
        </section>
    </main>
</body>
</html>

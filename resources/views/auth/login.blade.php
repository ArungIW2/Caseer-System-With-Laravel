<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Caseer System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
        <div class="mb-8">
            <p class="text-sm font-semibold text-indigo-600">CASEER SYSTEM</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Masuk ke sistem</h1>
            <p class="mt-2 text-sm text-slate-500">Retail management & point of sale.</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Ingat saya
            </label>

            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">
                Masuk
            </button>
        </form>
    </main>
</body>
</html>

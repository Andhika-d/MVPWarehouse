<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MVPWarehouse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            <div class="bg-gradient-to-br from-blue-600 via-indigo-700 to-slate-900 p-8 text-white lg:p-10">
                <div class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">
                    MVPWarehouse
                </div>
                <h1 class="mt-6 text-3xl font-bold leading-tight">
                    Selamat datang di sistem request barang
                </h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-blue-50">
                    Masuk untuk mengakses dashboard Gudang, HR, dan Admin secara terintegrasi.
                </p>

                <div class="mt-8 space-y-3 text-sm text-blue-50">
                    <div class="flex items-start gap-3 rounded-lg bg-white/10 p-3">
                        <span class="mt-1 text-base">✓</span>
                        <span>Ajukan permintaan barang dengan cepat dan terstruktur.</span>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg bg-white/10 p-3">
                        <span class="mt-1 text-base">✓</span>
                        <span>HR dapat memantau approval dan status request dari satu dashboard.</span>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg bg-white/10 p-3">
                        <span class="mt-1 text-base">✓</span>
                        <span>Riwayat permintaan tersimpan untuk kebutuhan audit dan monitoring.</span>
                    </div>
                </div>
            </div>

            <div class="p-8 lg:p-10">
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-2xl shadow-sm">
                        🔐
                    </div>
                    <h2 class="mt-5 text-2xl font-semibold text-slate-900">Masuk ke akun Anda</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Gunakan email dan password yang sudah terdaftar.
                    </p>
                </div>

                <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-4">
                    @csrf

                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:bg-white"
                            placeholder="nama@email.com"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:bg-white"
                            placeholder="Masukkan password"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700 min-h-[44px]"
                    >
                        Masuk
                    </button>
                </form>

                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-800">Catatan:</p>
                    <p class="mt-1">Jika Anda belum memiliki akun, silakan minta admin atau HR untuk membuatkan akun terlebih dahulu.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

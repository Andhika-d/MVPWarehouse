<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MVPWarehouse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-corpblue-950 text-slate-800 antialiased overflow-hidden">

    {{-- Full-screen background --}}
    <div class="fixed inset-0 -z-10 bg-corpblue-950">
        {{-- Background image --}}
        <img src="{{ asset('img/login-bg.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-70" loading="eager">
        {{-- Dark overlay --}}
        <div class="absolute inset-0 bg-corpblue-950/60"></div>
        {{-- Dot grid pattern --}}
        <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dotGrid" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="1" fill="rgba(255,255,255,0.05)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dotGrid)"/>
        </svg>
        {{-- Subtle radial glow --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-corpblue-500/8 blur-[120px]"></div>
    </div>

    {{-- Main content --}}
    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-8">

        {{-- Login card --}}
        <div class="login-card w-full max-w-4xl overflow-hidden rounded-2xl shadow-2xl lg:grid lg:grid-cols-[1.1fr_0.9fr]">

            {{-- ===== LEFT PANEL: BRANDING ===== --}}
            <div class="relative bg-corpblue-600 p-8 lg:p-10 text-white flex flex-col justify-between overflow-hidden">
                {{-- Dot pattern overlay on panel --}}
                <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="panelDots" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="1" cy="1" r="0.8" fill="rgba(255,255,255,0.04)"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#panelDots)"/>
                </svg>

                <div class="relative z-10">
                    {{-- Logo placeholder: monogram "TI" --}}
                    <div class="mb-8">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="32" cy="32" r="31" stroke="white" stroke-width="1.5" opacity="0.3"/>
                            <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle"
                                  font-family="Instrument Sans, system-ui, sans-serif" font-size="20" font-weight="700"
                                  fill="white" letter-spacing="2">TI</text>
                        </svg>
                    </div>

                    {{-- Company name --}}
                    <h1 class="text-xl lg:text-2xl font-bold tracking-[0.12em] uppercase leading-tight">
                        PT Taehang<br>Indonesia
                    </h1>
                    <p class="mt-1.5 text-base font-semibold text-corpblue-200">
                        Plan 2
                    </p>

                    {{-- Divider --}}
                    <div class="border-t border-white/10 my-6"></div>

                    {{-- System name --}}
                    <p class="text-xs font-medium text-corpblue-300 uppercase tracking-[0.2em]">
                        MVPWarehouse
                    </p>
                    <p class="mt-1 text-[11px] text-corpblue-200/60">
                        Sistem Manajemen Internal
                    </p>
                </div>

                {{-- Copyright --}}
                <p class="relative z-10 mt-10 text-[10px] text-corpblue-300/50">
                    &copy; 2026 PT Taehang Indonesia Plan 2
                </p>
            </div>

            {{-- ===== RIGHT PANEL: FORM ===== --}}
            <div class="bg-white border border-slate-200 p-8 lg:p-10 flex flex-col justify-center">
                <div class="text-center">
                    <h2 class="text-xl font-semibold text-slate-900">Masuk</h2>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Gunakan akun yang sudah terdaftar.
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
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                value="{{ old('email') }}"
                                required
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-corpblue-500 focus:bg-white"
                                placeholder="nama@email.com"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-corpblue-500 focus:bg-white"
                                placeholder="Masukkan password"
                            >
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-corpblue-500 focus:ring-corpblue-500 cursor-pointer">
                        <label for="remember" class="ml-2 block text-sm text-slate-600 cursor-pointer">Ingat saya</label>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-corpblue-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-corpblue-600 min-h-[44px]"
                    >
                        Masuk
                    </button>
                </form>
            </div>
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-xs text-white/30">
            &copy; 2026 PT Taehang Indonesia Plan 2. Hak Cipta Dilindungi.
        </p>
    </div>

</body>
</html>

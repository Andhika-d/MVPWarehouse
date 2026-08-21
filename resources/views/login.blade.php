<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MVPWarehouse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-corpblue-950 text-slate-800 antialiased">

    {{-- Full-screen background --}}
    <div class="fixed inset-0 -z-10 bg-corpblue-950">
        <img src="{{ asset('img/login-bg.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-100" loading="eager">
        <div class="absolute inset-0 bg-corpblue-950/60"></div>
        <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dotGrid" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="1" fill="rgba(255,255,255,0.05)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dotGrid)"/>
        </svg>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-corpblue-500/8 blur-[120px]"></div>
    </div>

    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-8 overflow-y-auto">

        {{-- ===== MOBILE: Compact branding (hidden on lg+) ===== --}}
        <div class="lg:hidden text-center mb-6">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" class="h-12 w-auto mx-auto mb-4">
            <h1 class="text-base font-bold tracking-[0.15em] uppercase text-white">PT Taehang Indonesia</h1>
            <p class="text-xs font-medium text-corpblue-300 tracking-[0.08em] mt-0.5">Plan 2</p>
        </div>

        {{-- ===== Login card ===== --}}
        <div class="login-card w-full max-w-4xl overflow-hidden rounded-2xl shadow-2xl lg:grid lg:grid-cols-[1.3fr_0.7fr]">

            {{-- ===== LEFT PANEL: BRANDING (hidden on mobile) ===== --}}
            <div class="hidden lg:flex relative bg-corpblue-600 px-10 py-12 text-white flex-col items-center justify-center text-center overflow-hidden">
                {{-- Background: dot grid --}}
                <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="panelDots" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="1" cy="1" r="0.8" fill="rgba(255,255,255,0.04)"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#panelDots)"/>
                </svg>
                {{-- Background: geometric diagonal lines --}}
                <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="geoLines" width="40" height="40" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                            <line x1="0" y1="0" x2="0" y2="40" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#geoLines)"/>
                </svg>

                <div class="relative z-10">
                    {{-- Logo --}}
                    <img src="{{ asset('img/logo.png') }}" alt="Logo PT Taehang Indonesia" class="h-[82px] w-auto mx-auto mb-6">

                    {{-- Company branding --}}
                    <h1 class="text-2xl font-bold tracking-[0.15em] uppercase leading-snug">PT Taehang Indonesia</h1>
                    <p class="text-base font-semibold text-corpblue-200 tracking-[0.08em] mt-1.5">Plan 2</p>

                    {{-- Divider 1 --}}
                    <div class="w-10 h-px bg-white/20 mx-auto my-7"></div>

                    {{-- System name — prominent --}}
                    <p class="typing-text text-lg font-bold text-white uppercase tracking-[0.2em]">MVPWAREHOUSE</p>
                    <p class="text-xs text-corpblue-200/70 mt-1.5">Internal Warehouse Management System</p>

                    {{-- Divider 2 --}}
                    <div class="w-10 h-px bg-white/20 mx-auto my-7"></div>

                    {{-- Capability points --}}
                    <div class="space-y-2.5 text-[14px] text-white/90">
                        <div class="flex items-center gap-3 justify-center">
                            <span class="text-corpblue-200/70 text-[10px]">◆</span>
                            <span>Request &amp; Approval</span>
                        </div>
                        <div class="flex items-center gap-3 justify-center">
                            <span class="text-corpblue-200/70 text-[10px]">◆</span>
                            <span>Stock Monitoring</span>
                        </div>
                        <div class="flex items-center gap-3 justify-center">
                            <span class="text-corpblue-200/70 text-[10px]">◆</span>
                            <span>Warehouse History</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== RIGHT PANEL: FORM ===== --}}
            <div class="bg-white border border-slate-200 p-6 sm:p-8 lg:p-8 flex flex-col justify-center">
                <div class="text-center">
                    <h2 class="text-lg font-semibold text-slate-900">Login</h2>
                    <p class="mt-1.5 text-sm text-slate-500">Gunakan akun yang sudah terdaftar.</p>
                </div>

                <form action="{{ route('login') }}" method="POST" class="mt-8 max-w-xs mx-auto w-full space-y-3">
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
                        Login
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

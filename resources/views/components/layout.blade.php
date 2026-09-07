<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Corporate Dashboard' }}</title>
    
    <!-- Memanggil Tailwind CSS Lokal Menggunakan Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- CSS Khusus untuk Mode Cetak PDF & Animasi Global -->
    <style>
        @media print {
            aside, header, .no-print {
                display: none !important;
            }
            main {
                padding: 0 !important;
                background-color: white !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }

        /* ── Modal Animations ──────────────────────────────────── */
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to   { opacity: 0; }
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(12px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes modalOut {
            from { opacity: 1; transform: translateY(0) scale(1); }
            to   { opacity: 0; transform: translateY(8px) scale(0.97); }
        }
        @keyframes iconPop {
            0%   { opacity: 0; transform: scale(0.5); }
            60%  { transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(8px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        .anim-fade-in       { animation: fadeIn 0.18s ease-out both; }
        .anim-fade-out      { animation: fadeOut 0.15s ease-in both; }
        .anim-modal-in      { animation: modalIn 0.2s cubic-bezier(0.16,1,0.3,1) both; }
        .anim-modal-out     { animation: modalOut 0.14s ease-in both; }
        .anim-icon-pop      { animation: iconPop 0.25s cubic-bezier(0.16,1,0.3,1) 0.08s both; }
        .anim-slide-in      { animation: slideInRight 0.2s ease-out both; }

        /* Toast slide-in from right */
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(24px) scale(0.95); }
            to   { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0) scale(1); }
            to   { opacity: 0; transform: translateX(24px) scale(0.95); }
        }
        .anim-toast-in  { animation: toastIn 0.25s cubic-bezier(0.16,1,0.3,1) both; }
        .anim-toast-out { animation: toastOut 0.18s ease-in both; }

        /* Dropdown */
        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(-6px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes dropdownOut {
            from { opacity: 1; transform: translateY(0) scale(1); }
            to   { opacity: 0; transform: translateY(-4px) scale(0.98); }
        }
        .anim-dropdown-in  { animation: dropdownIn 0.16s ease-out both; }
        .anim-dropdown-out { animation: dropdownOut 0.12s ease-in both; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

    @if(setting('dev_mode'))
    <div class="bg-red-600 text-white text-center text-xs font-bold px-4 py-2 no-print">
        ⚠️ DEV MODE AKTIF — Seluruh pembatasan role dibuka untuk keperluan testing.
        <a href="/admin/dashboard" class="underline hover:text-red-100 ml-1">Kelola</a>
    </div>
    @endif

    @if(session('impersonate_by'))
    <div class="bg-slate-900 text-white text-center text-xs font-semibold px-4 py-2 flex items-center justify-center space-x-3 no-print">
        <span>Anda sedang login sebagai <strong>{{ auth()->user()->name }}</strong> ({{ ucfirst(auth()->user()->role) }})</span>
        <form method="POST" action="/admin/impersonation/stop" class="inline">
            @csrf
            <button type="submit" class="bg-white text-slate-900 px-3 py-1 rounded-md font-bold hover:bg-slate-200 transition-colors cursor-pointer">Kembali ke Admin</button>
        </form>
    </div>
    @endif

    <div class="flex h-screen overflow-hidden">

    <!-- MOBILE OVERLAY -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden hidden" onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-200 ease-in-out">
            <div>
                <!-- Brand / Logo Perusahaan -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
                    @if(request()->is('hr*'))
                    <div class="w-9 h-9 rounded-lg bg-corpblue-500 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="text-white" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="flex flex-col leading-none min-w-0">
                        <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider leading-none truncate">PT Taehang Indonesia</span>
                        <span class="text-[10px] font-medium text-slate-400 leading-none mt-0.5">Plan 2</span>
                        <span class="text-[9px] font-semibold text-corpblue-500 uppercase tracking-widest leading-none mt-1.5">MVPWarehouse</span>
                    </div>
                    @elseif(request()->is('gudang*'))
                    <div class="w-9 h-9 rounded-lg bg-corpblue-500 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="text-white" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="flex flex-col leading-none min-w-0">
                        <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider leading-none truncate">PT Taehang Indonesia</span>
                        <span class="text-[10px] font-medium text-slate-400 leading-none mt-0.5">Plan 2</span>
                        <span class="text-[9px] font-semibold text-corpblue-500 uppercase tracking-widest leading-none mt-1.5">MVPWarehouse</span>
                    </div>
                    @elseif(request()->is('director*'))
                    <div class="w-9 h-9 rounded-lg bg-indigo-700 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="text-white" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div class="flex flex-col leading-none min-w-0">
                        <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider leading-none truncate">PT Taehang Indonesia</span>
                        <span class="text-[10px] font-medium text-slate-400 leading-none mt-0.5">Plan 2</span>
                        <span class="text-[9px] font-semibold text-corpblue-500 uppercase tracking-widest leading-none mt-1.5">MVPWarehouse</span>
                    </div>
                    @else
                    <div class="flex flex-col leading-none">
                        <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider leading-none">PT Taehang Indonesia</span>
                        <span class="text-[10px] font-medium text-slate-500 leading-none mt-0.5">Plan 2</span>
                        <span class="text-[10px] font-semibold text-corpblue-500 uppercase tracking-widest leading-none mt-1">MVPWarehouse</span>
                    </div>
                    @endif
                </div>

                <nav class="p-4 space-y-1">
                    <!-- PEMISAH LOGIKA PERAN BERDASARKAN URL -->
                        @if(request()->is('admin*'))
                        <!-- ================= MENU UNTUK ADMINISTRATOR ================= -->

                        <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.dashboard') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.dashboard'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                            <span>Dashboard</span>
                        </a>

                        <!-- Group: Master Data -->
                        <div class="px-4 pt-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Master Data</div>

                        <a href="{{ route('admin.items.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.items.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.items.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.items.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Master Barang</span>
                        </a>

                        <a href="{{ route('admin.import.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.import.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.import.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.import.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Import Barang</span>
                        </a>

                        <a href="{{ route('admin.locations.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.locations.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.locations.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.locations.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Lokasi Rak</span>
                        </a>

                        <!-- Group: Pengguna & Akses -->
                        <div class="px-4 pt-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pengguna & Akses</div>

                        <a href="{{ route('admin.users.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.users.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.users.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Daftar Pengguna</span>
                        </a>

                        <a href="{{ route('admin.location-changes.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.location-changes.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.location-changes.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.location-changes.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span>Approval Lokasi</span>
                        </a>

                        <!-- Group: Monitoring & Sistem -->
                        <div class="px-4 pt-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Monitoring & Sistem</div>

                        <a href="{{ route('admin.audit.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.audit.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.audit.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.audit.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Audit Log</span>
                        </a>

                        <a href="{{ route('admin.backups.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.backups.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.backups.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.backups.*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Backup & Pemulihan</span>
                        </a>

                        <a href="{{ route('admin.reset.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.reset.*') ? 'bg-red-50 text-red-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.reset.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-red-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('admin.reset.*') ? 'text-red-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Maintenance Data</span>
                        </a>

                        <a href="{{ route('admin.help-guides.index') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('admin.help-guides.*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('admin.help-guides.*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <span class="flex h-[18px] w-[18px] items-center justify-center rounded-full border text-[11px] font-bold {{ request()->routeIs('admin.help-guides.*') ? 'border-corpblue-500 text-corpblue-500' : 'border-slate-400 text-slate-400 group-hover:border-slate-600 group-hover:text-slate-600' }}">?</span>
                            <span>Kelola Bantuan</span>
                        </a>

                        @elseif(request()->is('hr*'))
                        <!-- ================= MENU UNTUK HRD ================= -->

                        <!-- Group: Workflow -->
                        <div class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Workflow</div>

                        <a href="/hr/dashboard" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('hr/dashboard') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('hr/dashboard'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('hr/dashboard') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                            <span>Dashboard Permintaan</span>
                        </a>

                        <a href="/hr/approval" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('hr/approval*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('hr/approval*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('hr/approval*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>Verifikasi & Approval</span>
                        </a>

                        <a href="/hr/daftar-belanja" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('hr/daftar-belanja*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('hr/daftar-belanja*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('hr/daftar-belanja*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span>Daftar Belanja Driver</span>
                        </a>

                        <!-- Divider -->
                        <div class="my-2 mx-4 border-t border-slate-100"></div>

                        <!-- Group: Monitoring -->
                        <div class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Monitoring</div>

                        <a href="/hr/stock" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('hr/stock*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('hr/stock*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('hr/stock*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Stok Barang</span>
                        </a>

                        <a href="/hr/history" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('hr/history*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('hr/history*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('hr/history*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>History Pengadaan Barang</span>
                        </a>
                    @elseif(request()->is('gudang*'))
                        <!-- ================= MENU UNTUK GUDANG ================= -->

                        <!-- Group: Operasional -->
                        <div class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Operasional</div>

                        <a href="/gudang/dashboard" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/dashboard') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/dashboard'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/dashboard') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                            <span>Dashboard</span>
                        </a>

                        <a href="/gudang/request-barang" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/request-barang*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/request-barang*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/request-barang*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span>Request Barang</span>
                        </a>

                        <a href="/gudang/penerimaan" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/penerimaan*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/penerimaan*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/penerimaan*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            <span>Penerimaan Barang</span>
                        </a>

                        <a href="/gudang/barang-keluar" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/barang-keluar*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/barang-keluar*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/barang-keluar*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-4 0V4m0 0l-2 2m2-2l2 2"/></svg>
                            <span>Barang Keluar</span>
                        </a>

                        <a href="/gudang/location-change" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/location-change*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/location-change*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/location-change*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span>Pengajuan Lokasi</span>
                        </a>

                        <!-- Divider -->
                        <div class="my-2 mx-4 border-t border-slate-100"></div>

                        <!-- Group: Stok & Riwayat -->
                        <div class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stok & Riwayat</div>

                        <a href="/gudang/stock" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/stock*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/stock*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/stock*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Stok Barang</span>
                        </a>

                        <a href="/gudang/movements" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/movements*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/movements*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/movements*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Riwayat Perubahan Stok</span>
                        </a>

                        <a href="/gudang/history" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->is('gudang/history*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->is('gudang/history*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->is('gudang/history*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>History Permintaan</span>
                        </a>
                    @elseif(request()->is('director*'))
                        <!-- ================= MENU UNTUK DIREKTUR ================= -->

                        <div class="px-4 pt-1 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Executive Monitoring</div>

                        <a href="{{ route('director.dashboard') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('director.dashboard') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('director.dashboard'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('director.dashboard') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                            <span>Dashboard</span>
                        </a>

                        <div class="px-4 pt-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Monitoring</div>

                        <a href="{{ route('director.requests') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('director.requests*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('director.requests*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('director.requests*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>Semua Request</span>
                        </a>

                        <a href="{{ route('director.stock') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('director.stock*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('director.stock*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('director.stock*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Stok Barang</span>
                        </a>

                        <a href="{{ route('director.movements') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('director.movements*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('director.movements*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('director.movements*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Perubahan Stok</span>
                        </a>

                        <a href="{{ route('director.timeline') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('director.timeline*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('director.timeline*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('director.timeline*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Timeline Aktivitas</span>
                        </a>

                        <a href="{{ route('director.issues') }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all relative {{ request()->routeIs('director.issues*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                            @if(request()->routeIs('director.issues*'))<span class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-corpblue-500"></span>@endif
                            <svg class="shrink-0 {{ request()->routeIs('director.issues*') ? 'text-corpblue-500' : 'text-slate-400 group-hover:text-slate-600' }}" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>Masalah & Analisis</span>
                        </a>
                    @endif
                </nav>
            </div>

                <!-- User Panel & Logout -->
            <div class="p-3 border-t border-slate-100">
                @if(request()->is('hr*'))
                <div class="flex items-center gap-3 px-3 py-2 mb-2 rounded-lg bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-corpblue-500 text-white flex items-center justify-center text-xs font-bold tracking-wider shrink-0">HR</div>
                    <div class="flex flex-col min-w-0 leading-none">
                        <span class="text-xs font-semibold text-slate-800 truncate">HRD</span>
                        <span class="text-[10px] text-slate-400 font-medium">HR Taehang</span>
                    </div>
                </div>
                @elseif(request()->is('gudang*'))
                <div class="flex items-center gap-3 px-3 py-2 mb-2 rounded-lg bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-corpblue-500 text-white flex items-center justify-center text-xs font-bold tracking-wider shrink-0">GD</div>
                    <div class="flex flex-col min-w-0 leading-none">
                        <span class="text-xs font-semibold text-slate-800 truncate">Gudang Utama</span>
                        <span class="text-[10px] text-slate-400 font-medium">PIC Gudang</span>
                    </div>
                </div>
                @elseif(request()->is('admin*'))
                <div class="flex items-center gap-3 px-3 py-2 mb-2 rounded-lg bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-slate-700 text-white flex items-center justify-center text-xs font-bold tracking-wider shrink-0">AD</div>
                    <div class="flex flex-col min-w-0 leading-none">
                        <span class="text-xs font-semibold text-slate-800 truncate">Administrator</span>
                        <span class="text-[10px] text-slate-400 font-medium">Super User</span>
                    </div>
                </div>
                @elseif(request()->is('director*'))
                <div class="flex items-center gap-3 px-3 py-2 mb-2 rounded-lg bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-indigo-700 text-white flex items-center justify-center text-xs font-bold tracking-wider shrink-0">DR</div>
                    <div class="flex flex-col min-w-0 leading-none">
                        <span class="text-xs font-semibold text-slate-800 truncate">Direktur</span>
                        <span class="text-[10px] text-slate-400 font-medium">Mr. Yang</span>
                    </div>
                </div>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="w-full" data-confirm="Yakin ingin keluar?" data-confirm-title="Keluar Akun" data-confirm-tone="danger" data-confirm-button="Keluar">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-xs font-medium transition-all cursor-pointer">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- 2. MAIN LAYOUT (Area Konten Dashboard) -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50">
            <!-- Topbar / Header Utama -->
                <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 shrink-0">
                    <div class="flex items-center gap-3">
                        <!-- Hamburger button (mobile only) -->
                        <button id="hamburgerBtn" onclick="openSidebar()" class="lg:hidden p-2 -ml-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer" aria-label="Buka menu">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <h1 class="text-base lg:text-lg font-semibold text-slate-900">{{ $headerTitle ?? 'Selamat Datang' }}</h1>
                    </div>
                    
                    <!-- Area Kanan Header (Notifikasi Dinamis & Profil Peran) -->
                    <div class="flex items-center space-x-3 md:space-x-6">
                        <a href="{{ route('help.index') }}" aria-label="Bantuan" title="Bantuan" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-sm font-bold text-slate-500 transition hover:border-corpblue-300 hover:bg-corpblue-50 hover:text-corpblue-600 cursor-pointer">?</a>
                        
                        <!-- NOTIFIKASI DROPDOWN -->
                        <x-notifications-dropdown />

                        <!-- INFORMASI PROFIL DINAMIS -->
                        <div class="flex items-center space-x-2 md:space-x-3">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ request()->is('admin*') ? 'Administrator' : (request()->is('hr*') ? 'HRD' : (request()->is('director*') ? 'Direktur' : 'Gudang Utama')) }}
                                </p>
                                <p class="text-xs text-slate-500 font-medium tracking-wide uppercase">
                                    {{ request()->is('admin*') ? 'Super User' : (request()->is('hr*') ? 'HR Taehang' : (request()->is('director*') ? 'Mr. Yang' : 'PIC Gudang')) }}
                                </p>
                            </div>
                            <div class="w-9 h-9 rounded-full {{ request()->is('admin*') ? 'bg-slate-800' : (request()->is('hr*') ? 'bg-indigo-600' : (request()->is('director*') ? 'bg-indigo-700' : 'bg-corpblue-500')) }} text-white flex items-center justify-center font-bold text-sm tracking-wider shadow-sm shrink-0">
                                {{ request()->is('admin*') ? 'AD' : (request()->is('hr*') ? 'HR' : (request()->is('director*') ? 'DR' : 'GD')) }}
                            </div>
                        </div>

                    </div>
                </header>

            <!-- Tempat Konten Dinamis Diisi -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>

    </div>

    {{-- MODAL ROOT: page modals rendered via $modals slot --}}
    <div id="modalRoot">{{ $modals ?? '' }}</div>

    {{-- ═══ GLOBAL CONFIRM MODAL ═══ --}}
    <div id="confirmModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-5 flex items-start gap-4">
                <div id="confirmModalIcon" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-red-100">
                    <svg class="text-red-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="confirmModalTitle" class="text-base font-bold text-slate-900">Konfirmasi</h3>
                    <p id="confirmModalMessage" class="text-sm text-slate-600 mt-1.5 leading-relaxed">Apakah Anda yakin?</p>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-200 rounded-xl font-medium transition-colors cursor-pointer">Batal</button>
                <button type="button" id="confirmModalBtn" onclick="confirmModalAction()" class="px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-colors cursor-pointer">Lanjutkan</button>
            </div>
        </div>
    </div>

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // ── Global Modal Utility ──────────────────────────────────────
        let _modalScrollPos = 0;

        function openModal(overlayId, opts) {
            opts = opts || {};
            var overlay = document.getElementById(overlayId);
            if (!overlay) return;
            _modalScrollPos = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + _modalScrollPos + 'px';
            document.body.style.width = '100%';
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            // animate overlay
            overlay.classList.remove('anim-fade-out');
            overlay.classList.add('anim-fade-in');
            // animate panel
            var panel = overlay.querySelector('.bg-white');
            if (panel) {
                panel.classList.remove('anim-modal-out');
                panel.classList.add('anim-modal-in');
            }
            var first = overlay.querySelector('input:not([type=hidden]),textarea');
            if (first) setTimeout(function(){ first.focus(); }, 60);
        }

        function closeModal(overlayId) {
            var overlay = document.getElementById(overlayId);
            if (!overlay) return;
            overlay.classList.remove('anim-fade-in');
            overlay.classList.add('anim-fade-out');
            var panel = overlay.querySelector('.bg-white');
            if (panel) {
                panel.classList.remove('anim-modal-in');
                panel.classList.add('anim-modal-out');
            }
            setTimeout(function() {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                overlay.classList.remove('anim-fade-out');
                if (panel) panel.classList.remove('anim-modal-out');
            }, 160);
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo(0, _modalScrollPos);
            _modalScrollPos = 0;
        }

        // global escape handler for the topmost visible modal
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            // confirm modal takes priority
            var confirmOverlay = document.getElementById('confirmModal');
            if (confirmOverlay && !confirmOverlay.classList.contains('hidden')) {
                closeConfirmModal();
                return;
            }
            var modals = document.querySelectorAll('#modalRoot > div:not(.hidden)');
            if (modals.length === 0) return;
            var top = modals[modals.length - 1];
            var closeBtn = top.querySelector('button[aria-label="Tutup"]');
            if (closeBtn) closeBtn.click();
        });

        // ── Global Confirm Modal ──────────────────────────────────────
        var _confirmForm = null;
        var _confirmScrollPos = 0;

        var _toneStyles = {
            danger:  { iconBg: 'bg-red-100', iconColor: 'text-red-600', btnBg: 'bg-red-600 hover:bg-red-700', icon: '<svg class="text-red-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>' },
            warning: { iconBg: 'bg-amber-100', iconColor: 'text-amber-600', btnBg: 'bg-amber-600 hover:bg-amber-700', icon: '<svg class="text-amber-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            info:    { iconBg: 'bg-corpblue-100', iconColor: 'text-corpblue-600', btnBg: 'bg-corpblue-600 hover:bg-corpblue-700', icon: '<svg class="text-corpblue-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            success: { iconBg: 'bg-emerald-100', iconColor: 'text-emerald-600', btnBg: 'bg-emerald-600 hover:bg-emerald-700', icon: '<svg class="text-emerald-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>' },
        };

        function openConfirmModal(form, message, opts) {
            opts = opts || {};
            _confirmForm = form;
            var tone = opts.tone || 'danger';
            var title = opts.title || 'Konfirmasi';
            var button = opts.button || 'Lanjutkan';
            var ts = _toneStyles[tone] || _toneStyles.danger;

            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;

            var iconEl = document.getElementById('confirmModalIcon');
            iconEl.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ' + ts.iconBg;
            iconEl.innerHTML = ts.icon;

            var btn = document.getElementById('confirmModalBtn');
            btn.textContent = button;
            btn.className = 'px-5 py-2 text-sm text-white rounded-xl font-semibold transition-colors cursor-pointer ' + ts.btnBg;

            _confirmScrollPos = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + _confirmScrollPos + 'px';
            document.body.style.width = '100%';

            var cm = document.getElementById('confirmModal');
            cm.classList.remove('hidden');
            cm.classList.add('flex');
            // animate overlay
            cm.classList.remove('anim-fade-out');
            cm.classList.add('anim-fade-in');
            // animate panel
            var panel = cm.querySelector('.bg-white');
            if (panel) {
                panel.classList.remove('anim-modal-out');
                panel.classList.add('anim-modal-in');
            }
            // animate icon
            iconEl.classList.remove('anim-icon-pop');
            void iconEl.offsetWidth;
            iconEl.classList.add('anim-icon-pop');
        }

        function closeConfirmModal() {
            var overlay = document.getElementById('confirmModal');
            overlay.classList.remove('anim-fade-in');
            overlay.classList.add('anim-fade-out');
            var panel = overlay.querySelector('.bg-white');
            if (panel) {
                panel.classList.remove('anim-modal-in');
                panel.classList.add('anim-modal-out');
            }
            setTimeout(function() {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                overlay.classList.remove('anim-fade-out');
                if (panel) panel.classList.remove('anim-modal-out');
            }, 160);
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo(0, _confirmScrollPos);
            _confirmScrollPos = 0;
            _confirmForm = null;
        }

        function confirmModalAction() {
            if (_confirmForm) {
                var form = _confirmForm;
                form.removeAttribute('data-confirm');
                form.submit();
            }
            closeConfirmModal();
        }

        // ── Auto-bind data-confirm on all forms ──────────────────────
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.tagName !== 'FORM') return;
            if (!form.hasAttribute('data-confirm')) return;

            e.preventDefault();
            openConfirmModal(form, form.getAttribute('data-confirm'), {
                title: form.getAttribute('data-confirm-title') || 'Konfirmasi',
                tone: form.getAttribute('data-confirm-tone') || 'danger',
                button: form.getAttribute('data-confirm-button') || 'Lanjutkan',
            });
        }, true);
    </script>
    {{ $scripts ?? '' }}

</body>
</html>

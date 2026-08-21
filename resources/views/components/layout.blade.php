<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Corporate Dashboard' }}</title>
    
    <!-- Memanggil Tailwind CSS Lokal Menggunakan Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- CSS Khusus untuk Mode Cetak PDF (Otomatis Sembunyikan Sidebar & Header) -->
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

        <!-- 1. SIDEBAR MENU (Pojok Kiri) - Dinamis Berdasarkan URL -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-200 ease-in-out">
            <div>
                <!-- Brand / Logo Perusahaan -->
                <div class="h-16 flex flex-col justify-center px-6 border-b border-slate-100 leading-tight">
                    <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider leading-none">PT Taehang Indonesia</span>
                    <span class="text-[10px] font-medium text-slate-500 leading-none mt-0.5">Plan 2</span>
                    <span class="text-[10px] font-semibold text-corpblue-500 uppercase tracking-widest leading-none mt-1">MVPWarehouse</span>
                </div>

                <nav class="p-4 space-y-1">
                    <!-- PEMISAH LOGIKA PERAN BERDASARKAN URL -->
                        @if(request()->is('admin*'))
                        <!-- ================= MENU UNTUK ADMINISTRATOR ================= -->
                        <div class="px-4 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pusat Kendali Admin</div>
                        
                        <!-- TAMBAHKAN ID PADA SETIAP BARIS SEPERTI DI BAWAH INI -->
                        <a href="#" id="menu-tab-master" onclick="switchTab('tab-master')" class="flex items-center space-x-3 px-4 py-2.5 bg-corpblue-50 text-corpblue-600 font-bold rounded-lg text-sm transition-all">
                            <span>🗄️</span> <span>Data Master</span>
                        </a>

                        <a href="#" id="menu-tab-users" onclick="switchTab('tab-users')" class="flex items-center space-x-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg text-sm font-semibold transition-all">
                            <span>👥</span> <span>Kelola Pengguna</span>
                        </a>

                        <a href="#" id="menu-tab-access" onclick="switchTab('tab-access')" class="flex items-center space-x-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg text-sm font-semibold transition-all">
                            <span>🔐</span> <span>Hak Akses Matriks</span>
                        </a>

                        <a href="#" id="menu-tab-audit" onclick="switchTab('tab-audit')" class="flex items-center space-x-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg text-sm font-semibold transition-all">
                            <span>📜</span> <span>Audit Log Global</span>
                        </a>

                        <a href="#" id="menu-tab-backup" onclick="switchTab('tab-backup')" class="flex items-center space-x-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg text-sm font-semibold transition-all">
                            <span>💾</span> <span>Backup & Pemulihan</span>
                        </a>

                        @elseif(request()->is('hr*'))
                        <!-- ================= MENU UNTUK HRD ================= -->
                        
                        <!-- Menu HR: Dashboard -->
                        <a href="/hr/dashboard" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('hr/dashboard') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
                            <span>Dashboard Permintaan</span>
                        </a>

                        <!-- Menu HR: Verifikasi & Approval -->
                        <a href="/hr/approval" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('hr/approval*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Verifikasi & Approval</span>
                        </a>

                        <!-- Menu HR: Daftar Belanja Driver -->
                        <a href="/hr/daftar-belanja" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('hr/daftar-belanja*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            <span>Daftar Belanja Driver</span>
                        </a>

                        <!-- Menu HR: History -->
                        <a href="/hr/history" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('hr/history*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>History Pengadaan Barang</span>
                        </a>
                    @elseif(request()->is('gudang*'))
                        <!-- ================= MENU UNTUK GUDANG ================= -->

                        <a href="/gudang/dashboard" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('gudang/dashboard') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                            <span>Dashboard</span>
                        </a>

                        <a href="/gudang/request-barang" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('gudang/request-barang*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Request Barang</span>
                        </a>

                        <a href="/gudang/history" class="flex items-center space-x-3 px-4 py-2.5 {{ request()->is('gudang/history*') ? 'bg-corpblue-50 text-corpblue-600 font-semibold' : 'text-slate-600 hover:bg-slate-50' }} rounded-lg text-sm transition-all">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>History Permintaan</span>
                        </a>
                    @endif
                </nav>
            </div>

            <!-- Tombol Logout -->
            <div class="p-4 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="flex w-full items-center space-x-3 px-4 py-2.5 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm transition-all">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Keluar / Logout</span>
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
                        
                        <!-- NOTIFIKASI DROPDOWN -->
                        <x-notifications-dropdown />

                        <!-- INFORMASI PROFIL DINAMIS -->
                        <div class="flex items-center space-x-2 md:space-x-3">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ request()->is('admin*') ? 'Administrator' : (request()->is('hr*') ? 'HRD' : 'Gudang Utama') }}
                                </p>
                                <p class="text-xs text-slate-500 font-medium tracking-wide uppercase">
                                    {{ request()->is('admin*') ? 'Super User' : (request()->is('hr*') ? 'HR Taehang' : 'PIC Gudang') }}
                                </p>
                            </div>
                            <div class="w-9 h-9 rounded-full {{ request()->is('admin*') ? 'bg-slate-800' : (request()->is('hr*') ? 'bg-indigo-600' : 'bg-corpblue-500') }} text-white flex items-center justify-center font-bold text-sm tracking-wider shadow-sm shrink-0">
                                {{ request()->is('admin*') ? 'AD' : (request()->is('hr*') ? 'HR' : 'GD') }}
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
    </script>

</body>
</html>

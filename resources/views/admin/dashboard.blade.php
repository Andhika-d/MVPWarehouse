<x-layout>
    <x-slot:title>Panel Administrator - CorpLogistics</x-slot:title>
    <x-slot:headerTitle>Pusat Kendali Sistem (Super User)</x-slot:headerTitle>

    <div class="space-y-6">
        
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <!-- ================= BILAH NAVIGASI 5 TAB KONTROL UTAMA ================= -->
        <div class="bg-white border border-slate-200 rounded-xl p-1.5 shadow-sm overflow-x-auto flex flex-nowrap gap-1 text-xs font-bold text-slate-600 no-print">
            <button onclick="switchTab('tab-master')" id="btn-tab-master" class="tab-btn px-4 py-2 bg-blue-600 text-white rounded-lg shadow-xs cursor-pointer transition-all">🗄️ Data Master</button>
            <button onclick="switchTab('tab-users')" id="btn-tab-users" class="tab-btn px-4 py-2 hover:bg-slate-50 hover:text-slate-900 rounded-lg cursor-pointer transition-all">👥 Kelola Pengguna</button>
            <button onclick="switchTab('tab-access')" id="btn-tab-access" class="tab-btn px-4 py-2 hover:bg-slate-50 hover:text-slate-900 rounded-lg cursor-pointer transition-all">🔐 Hak Akses Matriks</button>
            <button onclick="switchTab('tab-audit')" id="btn-tab-audit" class="tab-btn px-4 py-2 hover:bg-slate-50 hover:text-slate-900 rounded-lg cursor-pointer transition-all">📜 Audit Log Global</button>
            <button onclick="switchTab('tab-backup')" id="btn-tab-backup" class="tab-btn px-4 py-2 hover:bg-slate-50 hover:text-slate-900 rounded-lg cursor-pointer transition-all">💾 Backup & Pemulihan</button>
        </div>

        <!-- =========================================================================
             TAB 1: PENGELOLAAN DATA MASTER
        ============================================================================= -->
        <div id="tab-master" class="tab-content space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Master Data Inventaris Barang (`mst_items`)</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Kamus data logistik pengunci drop-down pilihan form Gudang.</p>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <button onclick="openImportModal()" class="bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-lg cursor-pointer hover:bg-slate-800 transition-all">📥 Import Excel</button>
                        <button onclick="openBarangModal()" class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg cursor-pointer hover:bg-blue-700 transition-all">+ Tambah Barang</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="master-barang-table-body" class="w-full text-left text-xs font-medium text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-3 px-6">Kode</th>
                                <th class="py-3 px-6">Nama Barang</th>
                                <th class="py-3 px-6">Size</th>
                                <th class="py-3 px-6">Satuan</th>
                                <th class="py-3 px-6">Sisa Stok</th>
                                <th class="py-3 px-6">Lokasi Rak</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($items as $item)
                            <tr>
                                <td class="py-3.5 px-6 font-bold text-slate-500">BRG-{{ str_pad($item->id, 3, '0', STR_PAD_LEFT) }}</td>
                                <td class="py-3.5 px-6 font-bold text-slate-900 text-sm">{{ $item->name }}</td>
                                <td class="py-3.5 px-6 text-slate-600">{{ $item->size ?? '-' }}</td>
                                <td class="py-3.5 px-6">{{ $item->unit }}</td>
                                <td class="py-3.5 px-6">
                                    <span class="font-bold text-slate-900">{{ $item->stock }} {{ $item->unit }}</span>
                                </td>
                                <td class="py-3.5 px-6 font-semibold">{{ $item->rack_location }}</td>
                                <td class="py-3.5 px-6 text-center space-x-2">
                                    <button type="button" onclick="fillEditForm({{ $item->id }})" class="text-blue-600 hover:underline cursor-pointer">Edit</button>
                                    <form method="POST" action="/admin/items/{{ $item->id }}" class="inline" onsubmit="return confirm('Hapus item ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline cursor-pointer">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-4 px-6 text-center text-slate-500">Belum ada data master barang.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100">
                    {{ $items->links() }}
                </div>
            </div>
        </div>

        <div id="editItemModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900">Edit Item Master</h3>
                    <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
                </div>
                <form id="editItemForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nama Barang</label>
                            <input id="editName" name="name" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Size / Ukuran (opsional)</label>
                            <input id="editSize" name="size" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Contoh: 44 Cm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Lokasi Rak</label>
                            <select id="editRack" name="rack_location" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2">
                                <option value="A">Rak A</option>
                                <option value="B">Rak B</option>
                                <option value="C">Rak C</option>
                                <option value="D">Rak D</option>
                                <option value="E">Rak E</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Stok</label>
                                <input id="editStock" name="stock" type="number" min="0" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Satuan</label>
                                <select id="editUnit" name="unit" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2">
                                    @foreach(App\Models\Item::UNITS as $unit)
                                        <option value="{{ $unit }}">{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="closeEditModal()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================================
             TAB 2: KELOLA PENGGUNA (USER MANAGEMENT)
        ============================================================================= -->
        <div id="tab-users" class="tab-content space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Daftar Pengguna Sistem Logistik</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Manajemen otoritas akun karyawan logistik dan manajemen.</p>
                    </div>

                    <button onclick="openUserModal()" type="button" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg cursor-pointer transition-all shadow-xs">
                        + Tambah Pengguna
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-medium text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-3 px-6">Nama Lengkap</th>
                                <th class="py-3 px-6">Username / Email</th>
                                <th class="py-3 px-6">Peran (Role)</th>
                                <th class="py-3 px-6">Status Akses</th>
                                <th class="py-3 px-6 text-center">Aksi Cepat Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($users as $user)
                            <tr id="user-row-{{ $user->id }}">
                                <td class="py-4 px-6 font-bold text-slate-900">{{ $user->name }}</td>
                                <td class="py-4 px-6 text-slate-500">{{ $user->email }}</td>
                                <td class="py-4 px-6"><span class="px-2 py-0.5 {{ $user->role === 'admin' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($user->role === 'hr' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }} rounded font-bold text-[10px]">{{ ucfirst($user->role) }}</span></td>
                                <td class="py-4 px-6" id="status-user-{{ $user->id }}">
                                    <span class="px-2 py-0.5 {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-100 text-red-700' }} rounded font-semibold text-[10px]">
                                        {{ $user->is_active ? '🟢 Aktif' : '🔴 Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center space-x-2">
                                    @if($user->role !== 'admin' && $user->id !== auth()->id())
                                    <form method="POST" action="/admin/users/{{ $user->id }}/login-as" class="inline" onsubmit="return confirmLoginAs({{ $user->id }});">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100 text-[11px] font-semibold text-indigo-700 cursor-pointer">Login As</button>
                                    </form>
                                    @endif
                                    <form method="POST" action="/admin/users/{{ $user->id }}/toggle-status" class="inline" onsubmit="return confirm('Ubah status akun ini?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 {{ $user->is_active ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }} rounded-md hover:bg-slate-100 text-[11px] font-semibold cursor-pointer">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/users/{{ $user->id }}/role" class="inline">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700">
                                            <option value="gudang" {{ $user->role === 'gudang' ? 'selected' : '' }}>Gudang</option>
                                            <option value="hr" {{ $user->role === 'hr' ? 'selected' : '' }}>HR</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    </form>
                                    <form method="POST" action="/admin/users/{{ $user->id }}/reset-password" class="inline" onsubmit="return confirm('Reset password akun ini (password sementara acak, wajib diganti saat login)?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-md hover:bg-slate-100 text-[11px] font-semibold cursor-pointer">Reset Password</button>
                                    </form>
                                    <form method="POST" action="/admin/users/{{ $user->id }}" class="inline" onsubmit="return confirm('Hapus akun ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 bg-red-50 border border-red-200 rounded-md hover:bg-red-100 text-[11px] font-semibold text-red-700 cursor-pointer">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-4 px-6 text-center text-slate-500">Belum ada pengguna terdaftar.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

        <!-- =========================================================================
             TAB 3: HAK AKSES MATRIKS (PERMISSION GRID + DEVELOPER MODE)
        ============================================================================= -->
        <div id="tab-access" class="tab-content space-y-6 hidden">
            <!-- KARTU DEVELOPER MODE -->
            <div class="bg-white rounded-xl border {{ setting('dev_mode') ? 'border-red-300' : 'border-slate-200' }} shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 {{ setting('dev_mode') ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400' }} rounded-lg flex items-center justify-center shrink-0">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Developer Mode (Mode Testing)</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Buka seluruh pembatasan role agar semua halaman (Gudang, HR, Admin) bisa diakses bebas untuk testing.</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 shrink-0">
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold {{ setting('dev_mode') ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ setting('dev_mode') ? '● AKTIF' : '○ NONAKTIF' }}
                        </span>
                        <form method="POST" action="/admin/settings/dev-mode/toggle">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition-all shadow-xs {{ setting('dev_mode') ? 'bg-slate-900 text-white hover:bg-slate-700' : 'bg-red-600 text-white hover:bg-red-700' }}">
                                {{ setting('dev_mode') ? 'Matikan Dev Mode' : 'Aktifkan Dev Mode' }}
                            </button>
                        </form>
                    </div>
                </div>
                @if(setting('dev_mode'))
                <div class="px-5 py-3 bg-red-50 border-b border-red-100 text-xs text-red-700 font-medium">
                    ⚠️ Saat ini semua role dapat membuka halaman mana pun (gudang, HR, dan admin). Aktifkan hanya untuk keperluan testing dan segera matikan sebelum digunakan di produksi. Aktivasi tercatat di Audit Log.
                </div>
                @endif
            </div>

            <!-- MATRIKS STATUS AKSES -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-900">Matriks Kontrol Otoritas Menu (Permission Matrix)</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Status akses nyata tiap role. Enforcement tetap melalui middleware route; saat Developer Mode aktif seluruh akses terbuka.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-center text-xs font-semibold text-slate-700 border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-4 px-6 text-left w-48">Tipe Peran (Role)</th>
                                <th class="py-4 px-6">Buat Request</th>
                                <th class="py-4 px-6">Lihat History Sendiri</th>
                                <th class="py-4 px-6">Verifikasi Berkas</th>
                                <th class="py-4 px-6">Cetak Nota Driver</th>
                                <th class="py-4 px-6">Kelola Data Master</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-900">
                            @php $open = setting('dev_mode'); @endphp
                            <!-- Baris Role Gudang -->
                            <tr class="hover:bg-slate-50/40">
                                <td class="py-4 px-6 text-left font-bold text-blue-600">📦 Staff Gudang</td>
                                <td class="py-4 px-6"><input type="checkbox" checked disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-blue-600' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" checked disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-blue-600' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" {{ $open ? 'checked' : '' }} disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-slate-300' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" {{ $open ? 'checked' : '' }} disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-slate-300' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" {{ $open ? 'checked' : '' }} disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-slate-300' }}"></td>
                            </tr>
                            <!-- Baris Role HRD -->
                            <tr class="hover:bg-slate-50/40">
                                <td class="py-4 px-6 text-left font-bold text-indigo-600">💼 HRD / Atasan</td>
                                <td class="py-4 px-6"><input type="checkbox" {{ $open ? 'checked' : '' }} disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-slate-300' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" {{ $open ? 'checked' : '' }} disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-slate-300' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" checked disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-blue-600' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" checked disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-blue-600' }}"></td>
                                <td class="py-4 px-6"><input type="checkbox" {{ $open ? 'checked' : '' }} disabled class="w-4 h-4 {{ $open ? 'text-red-500' : 'text-slate-300' }}"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-xs text-slate-500 font-medium">
                    <span>Menampilkan 2 peran internal terdaftar dalam sistem v1.0</span>
                    @if($open)
                    <span class="font-bold text-red-600">DEV MODE: SELURUH AKSES TERBUKA</span>
                    @else
                    <span>Halaman 1 dari 1</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- =========================================================================
             TAB 4: AUDIT LOG GLOBAL (HISTORY AUDIT SELURUH USER)
        ============================================================================= -->
        <div id="tab-audit" class="tab-content space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-900">Jejak Langkah Sistem (Audit Trail Activity Log)</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Rekaman hitam di atas putih seluruh aktivitas user demi keamanan data masa depan.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-medium text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-3.5 px-6">Waktu Sistem</th>
                                <th class="py-3.5 px-6">Pengguna (User)</th>
                                <th class="py-3.5 px-6">Peran</th>
                                <th class="py-3.5 px-6">Jenis Aksi</th>
                                <th class="py-3.5 px-6">Detail Rekap Kinerja</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-900">
                            @forelse($auditLogs as $log)
                            <tr class="hover:bg-slate-50/40">
                                <td class="py-3.5 px-6 text-slate-400 font-medium">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                                <td class="py-3.5 px-6 text-sm font-bold">{{ $log->user?->name ?? 'Sistem' }}</td>
                                <td class="py-3.5 px-6 text-slate-500">{{ $log->user?->role ?? '-' }}</td>
                                <td class="py-3.5 px-6 {{ $log->action === 'created_user' ? 'text-blue-600' : 'text-amber-600' }}">{{ strtoupper(str_replace('_', ' ', $log->action)) }}</td>
                                <td class="py-3.5 px-6 text-xs text-slate-500 font-normal">{{ $log->details }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-4 px-6 text-center text-slate-500">Belum ada log audit.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Table Limit untuk Audit Log -->
                <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-xs text-slate-500 font-medium">
                    <div class="flex items-center space-x-2">
                        <span>Tampilkan</span>
                        <select class="px-2 py-1 bg-white border border-slate-200 rounded-md focus:outline-none cursor-pointer font-semibold text-slate-700">
                            <option value="10">10 Baris</option>
                            <option value="25">25 Baris</option>
                        </select>
                        <span>dari arsip audit global</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span>Halaman <b>1</b> dari <b>1</b></span>
                        <div class="inline-flex space-x-1">
                            <button class="p-1.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" disabled>&larr;</button>
                            <button class="p-1.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-lg cursor-not-allowed" disabled>&rarr;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             TAB 5: PUSAT CADANGAN DATA (BACKUP & RESTORE CENTER)
        ============================================================================= -->
        <div id="tab-backup" class="tab-content space-y-6 hidden">
            <!-- Tombol Pemicu Backup Utama -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Sistem Pencadangan Penuh Basis Data (Database .SQLite)</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Amankan seluruh logbook, data master barang, user, dan hak akses menjadi berkas kompresi aman (.ZIP) sebelum ekspansi produksi.</p>
                </div>
                <form method="POST" action="/admin/backups">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md cursor-pointer transition-all shrink-0">
                        💾 Jalankan Backup Sekarang
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar File Backup -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 font-bold text-xs text-slate-400 uppercase tracking-wider">
                    Arsip Berkas Cadangan Tersedia
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-semibold text-slate-700">
                        <thead class="bg-slate-50/50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-3 px-6">Nama Berkas Cadangan</th>
                                <th class="py-3 px-6">Ukuran File</th>
                                <th class="py-3 px-6">Waktu Cadangan</th>
                                <th class="py-3 px-6 text-center">Pulihkan / Unduh / Hapus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-900">
                            @forelse ($backups as $backup)
                                <tr>
                                    <td class="py-3.5 px-6 font-mono text-blue-600">{{ $backup['name'] }}</td>
                                    <td class="py-3.5 px-6 text-slate-500">{{ $backup['size'] }}</td>
                                    <td class="py-3.5 px-6 font-medium text-slate-400">{{ $backup['time'] }}</td>
                                    <td class="py-3.5 px-6 text-center space-x-3">
                                        <form method="POST" action="/admin/backups/{{ $backup['name'] }}/restore" class="inline" onsubmit="return confirm('Pulihkan database dari backup {{ $backup['name'] }}? Data saat ini akan diganti (cadangan otomatis dibuat sebelumnya).')">
                                            @csrf
                                            <button type="submit" class="text-emerald-600 hover:underline cursor-pointer">Pulihkan</button>
                                        </form>
                                        <a href="/admin/backups/{{ $backup['name'] }}/download" class="text-blue-600 hover:underline cursor-pointer">Unduh (.ZIP)</a>
                                        <form method="POST" action="/admin/backups/{{ $backup['name'] }}" class="inline" onsubmit="return confirm('Hapus backup {{ $backup['name'] }}? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline cursor-pointer">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-slate-400">Belum ada file backup. Klik "Jalankan Backup Sekarang" untuk membuat cadangan pertama.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Tambah User Baru -->
        <div id="userModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center hidden z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-900">Daftar Akun Karyawan Baru</h3>
                    <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600 text-2xl font-light leading-none cursor-pointer">&times;</button>
                </div>
                <form method="POST" action="/admin/users" class="space-y-4">
                    @csrf
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Lengkap Karyawan</label>
                            <input name="name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900" placeholder="Contoh: Muhammad Budi">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Username / Alamat Email</label>
                            <input name="email" type="email" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900" placeholder="budi@corp.com">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Kata Sandi <span class="text-slate-400">(opsional)</span></label>
                            <input name="password" type="password" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900" placeholder="Kosongkan untuk password acak otomatis">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Otoritas Peran (Role)</label>
                            <select name="role" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-800 font-semibold">
                                <option value="gudang">📦 Staff Gudang Logistik</option>
                                <option value="hr">💼 HRD / Atasan Verifikator</option>
                                <option value="admin">🛡️ Administrator</option>
                            </select>
                        </div>
                        <div class="bg-blue-50/50 p-3 rounded-lg text-[10px] text-blue-700 leading-relaxed">
                            ℹ️ <b>Password sementara:</b> Kosongkan kolom kata sandi agar sistem membuat password acak yang hanya ditampilkan <b>sekali</b> di halaman ini. Pengguna wajib mengganti password saat login pertama.
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2">
                        <button onclick="closeUserModal()" type="button" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm cursor-pointer">Daftarkan Akun</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Barang Baru -->
        <div id="barangModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center hidden z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden flex flex-col">
                <!-- Header Modal -->
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-900">Tambah Kamus Barang Baru</h3>
                    <button onclick="closeBarangModal()" type="button" class="text-slate-400 hover:text-slate-600 text-2xl font-light leading-none cursor-pointer">&times;</button>
                </div>
                <!-- Body Form Modal -->
                <form method="POST" action="/admin/items" class="space-y-3">
                    @csrf
                    <div class="p-5 space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Barang</label>
                            <input name="name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900" placeholder="Contoh: Lakban Bening 2 Inch">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Size / Ukuran (opsional)</label>
                            <input name="size" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900" placeholder="Contoh: 44 Cm — kosongkan jika barang tanpa size">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Satuan</label>
                                <select name="unit" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-800">
                                    @foreach(App\Models\Item::UNITS as $unit)
                                        <option value="{{ $unit }}">{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Stok Awal</label>
                                <input name="stock" type="number" value="10" min="0" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Lokasi Penyimpanan Rak</label>
                            <select name="rack_location" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-800">
                                <option value="A">Rak A</option>
                                <option value="B">Rak B</option>
                                <option value="C">Rak C</option>
                                <option value="D">Rak D</option>
                                <option value="E">Rak E</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2">
                        <button onclick="closeBarangModal()" type="button" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm cursor-pointer">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Import Master Barang dari Excel -->
        <div id="importModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center hidden z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-900">Import Master Barang dari Excel</h3>
                    <button onclick="closeImportModal()" type="button" class="text-slate-400 hover:text-slate-600 text-2xl font-light leading-none cursor-pointer">&times;</button>
                </div>
                <form method="POST" action="/admin/items/import" enctype="multipart/form-data">
                    @csrf
                    <div class="p-5 space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">File Excel (.xlsx)</label>
                            <input type="file" name="file" accept=".xlsx" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-900">
                            <p class="mt-1.5 text-[11px] text-slate-500 leading-relaxed">
                                Format kolom: <b>Nama Barang | Size | Qty</b>. Satuan ditulis langsung di kolom Qty (contoh: <b>4 pcs</b>, <b>2 Roll</b>, <b>4kg</b>). Angka polos otomatis dihitung sebagai <b>Pcs</b>. Size kosong untuk barang tanpa ukuran (mis. Karpet).
                            </p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Lokasi Rak (dipakai untuk semua baris)</label>
                            <select name="rack_location" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-blue-600 focus:bg-white transition-all text-slate-800">
                                <option value="A">Rak A</option>
                                <option value="B">Rak B</option>
                                <option value="C">Rak C</option>
                                <option value="D">Rak D</option>
                                <option value="E">Rak E</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end space-x-2">
                        <button onclick="closeImportModal()" type="button" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg shadow-sm cursor-pointer">Import Sekarang</button>
                    </div>
                </form>
            </div>
        </div>

    <script>
        const masterItems = @json($masterItems);

        const usersData = @json($usersData);

        function fillEditForm(id) {
            const item = masterItems[id];
            if (!item) return;
            document.getElementById('editItemForm').action = '/admin/items/' + id;
            document.getElementById('editName').value = item.name;
            document.getElementById('editSize').value = item.size || '';
            document.getElementById('editRack').value = item.rack_location;
            document.getElementById('editStock').value = item.stock;
            document.getElementById('editUnit').value = item.unit;
            document.getElementById('editItemModal').classList.remove('hidden');
            document.getElementById('editItemModal').classList.add('flex');
        }

        function confirmLoginAs(id) {
            const user = usersData[id];
            if (!user) return false;
            return confirm('Login sebagai ' + user.name + ' (' + user.role + ')? Anda akan dialihkan ke akun tersebut untuk testing.');
        }

        function closeEditModal() {
            document.getElementById('editItemModal').classList.add('hidden');
            document.getElementById('editItemModal').classList.remove('flex');
        }

        // 1. Fungsi Mekanik Perpindahan 5 Tab Secara Instan
        function switchTab(targetTabId) {
            // Sembunyikan semua konten tab
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
            
            // Kembalikan gaya semua tombol tab ke kondisi pasif
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => {
                btn.className = "tab-btn px-4 py-2 hover:bg-slate-50 hover:text-slate-900 rounded-lg cursor-pointer transition-all";
            });

            // Munculkan tab target yang dipilih
            document.getElementById(targetTabId).classList.remove('hidden');
                        // Beri warna biru aktif pada tombol tab yang sedang dibuka
            document.getElementById('btn-' + targetTabId).className = "tab-btn px-4 py-2 bg-blue-600 text-white rounded-lg shadow-xs cursor-pointer transition-all";

            // Sinkronkan highlight pada menu sidebar admin
            const activeKey = targetTabId.replace('tab-', '');
            const sidebarLinks = document.querySelectorAll('[id^="menu-tab-"]');
            sidebarLinks.forEach(link => {
                const key = link.id.replace('menu-tab-', '');
                link.className = key === activeKey
                    ? "flex items-center space-x-3 px-4 py-2.5 bg-blue-50 text-blue-600 font-bold rounded-lg text-sm transition-all"
                    : "flex items-center space-x-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg text-sm font-semibold transition-all";
            });
        }

        function toggleUserAkses(statusCellId, btnId) {
            const container = document.getElementById(statusCellId);
            const button = document.getElementById(btnId);
            
            if (container.innerText.includes('Aktif')) {
                // Mengubah status menjadi Nonaktif
                container.innerHTML = '<span class="px-2 py-0.5 bg-red-100 text-slate-900 rounded font-bold text-[10px]">🔴 Diblokir / Nonaktif</span>';
                button.innerText = "Aktifkan";
                button.className = "px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md hover:bg-emerald-600 hover:text-white text-[11px] font-bold cursor-pointer transition-colors";
            } else {
                // Mengubah kembali status menjadi Aktif
                container.innerHTML = '<span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded font-semibold text-[10px]">🟢 Aktif</span>';
                button.innerText = "Nonaktifkan";
                button.className = "px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-md hover:bg-red-600 hover:text-white text-[11px] font-bold cursor-pointer transition-colors";
            }
        }

        // 3. Fungsi Simulasi Membuat File Backup Basis Data Baru

        //Tambah Barang Baru
        let barangCounter = document.querySelectorAll('#master-barang-table-body tr').length;
        
        function openBarangModal() { 
            document.getElementById('barangModal').classList.remove('hidden'); 
            document.getElementById('input-nama-barang').focus(); 
        }
        function closeBarangModal() { 
            document.getElementById('barangModal').classList.add('hidden'); 
            document.getElementById('input-nama-barang').value = ''; 
        }

        function openImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
            document.getElementById('importModal').classList.add('flex');
        }
        function closeImportModal() {
            document.getElementById('importModal').classList.add('hidden');
            document.getElementById('importModal').classList.remove('flex');
        }
        
        function executeSimulasiTambahBarang() {
            let nama = document.getElementById('input-nama-barang').value;
            let satuan = document.getElementById('input-satuan-barang').value;
            let stok = document.getElementById('input-stok-barang').value;
            let rak = document.getElementById('input-rak-barang').value;
            
            if (!nama.trim()) { alert('Nama barang tidak boleh dikosongkan!'); return; }
            barangCounter++;
            
            const tableBody = document.getElementById('master-barang-table-body');
            const newRow = document.createElement('tr');
            const newId = 'barang-row-' + barangCounter;
            
            newRow.id = newId;
            newRow.className = "divide-y divide-slate-100 bg-white hover:bg-slate-50/50 transition-colors animate-in fade-in duration-200";
            newRow.innerHTML = `
                <td class="py-3.5 px-6 font-bold text-slate-500">BRG-00${barangCounter}</td>
                <td class="py-3.5 px-6 font-bold text-slate-900 text-sm">${nama}</td>
                <td class="py-3.5 px-6">${satuan}</td>
                <td class="py-3.5 px-6 text-blue-600 font-bold">${stok}</td>
                <td class="py-3.5 px-6 font-semibold">${rak}</td>
                <td class="py-3.5 px-6 text-center space-x-3">
                    <button onclick="editBarangSimulasi('${newId}', '${nama}')" class="text-blue-600 font-semibold hover:underline cursor-pointer">Edit</button>
                    <button onclick="hapusBarangSimulasi('${newId}')" class="text-red-600 font-semibold hover:underline cursor-pointer">Hapus</button>
                </td>
            `;
            tableBody.insertBefore(newRow, tableBody.firstChild);
            closeBarangModal();
            alert('📦 Sukses!\nBarang baru "' + nama + '" berhasil ditambahkan ke kamus data master.');
        }

        //Modal user baru
        function openUserModal() { 
            document.getElementById('userModal').classList.remove('hidden'); 
            document.getElementById('input-nama-user').focus(); 
        }
        function closeUserModal() { 
            document.getElementById('userModal').classList.add('hidden'); 
            document.getElementById('input-nama-user').value = ''; 
            document.getElementById('input-email-user').value = ''; 
        }

        function executeSimulasiTambahUser() {
            let nama = document.getElementById('input-nama-user').value;
            let email = document.getElementById('input-email-user').value;
            let role = document.getElementById('input-role-user').value;
            
            if (!nama.trim() || !email.trim()) { alert('Semua kolom form wajib diisi lengkap!'); return; }
            userCounter++;
            
            let roleBadge = role === 'HRD' 
                ? `<span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded font-bold text-[10px]">HRD / Atasan</span>`
                : `<span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded font-bold text-[10px]">Staff Gudang</span>`;
            
            const tableBody = document.querySelector('#tab-users tbody');
            const newRow = document.createElement('tr');
            const statusId = 'status-user-' + userCounter;
            const btnId = 'btn-toggle-user-' + userCounter;
            
            newRow.className = "divide-y divide-slate-100 bg-white hover:bg-slate-50/50 transition-colors animate-in fade-in duration-200";
            newRow.innerHTML = `
                <td class="py-4 px-6 font-bold text-slate-900">${nama}</td>
                <td class="py-4 px-6 text-slate-500">${email}</td>
                <td class="py-4 px-6">${roleBadge}</td>
                <td class="py-4 px-6" id="${statusId}"><span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded font-semibold text-[10px]">🟢 Aktif</span></td>
                <td class="py-4 px-6 text-center space-x-2">
                    <button onclick="alert('🔒 Reset password untuk ${nama} dilakukan dari tabel pengguna. Password sementara acak baru akan ditampilkan sekali dan wajib diganti saat login.')" class="px-2.5 py-1 bg-white border border-slate-200 rounded-md hover:bg-slate-50 text-[11px] font-semibold cursor-pointer shadow-2xs">Reset Password</button>
                    <button id="${btnId}" onclick="toggleUserAkses('${statusId}', '${btnId}')" class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-md hover:bg-red-600 hover:text-white text-[11px] font-bold cursor-pointer transition-colors">Nonaktifkan</button>
                </td>
            `;
            tableBody.insertBefore(newRow, tableBody.firstChild);
            closeUserModal();
            alert('👥 Sukses!\nAkun baru atas nama "' + nama + '" resmi terdaftar aktif.');
        }
    </script>
</x-layout>


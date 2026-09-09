<x-layout :title="'Kelola Pengguna — MVPWarehouse'" :headerTitle="'Kelola Pengguna'">
    <div class="space-y-6">

        {{-- Toolbar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <select name="role" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Role</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="hr" {{ request('role') === 'hr' ? 'selected' : '' }}>HR</option>
                    <option value="gudang" {{ request('role') === 'gudang' ? 'selected' : '' }}>Gudang</option>
                </select>
                @if(request()->filled('search') || (request()->filled('role') && request('role') !== 'all'))
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
                <button type="button" onclick="openModal('addUserModal')" class="px-4 py-2 bg-corpblue-500 hover:bg-corpblue-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer">+ Tambah</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Role</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $user->role === 'admin' ? 'bg-slate-100 text-slate-700' : ($user->role === 'hr' ? 'bg-indigo-50 text-indigo-700' : 'bg-corpblue-50 text-corpblue-700') }} text-xs font-semibold">{{ strtoupper($user->role) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }} text-xs font-semibold">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" data-confirm="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user {{ $user->name }}?" data-confirm-title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} User" data-confirm-tone="{{ $user->is_active ? 'warning' : 'success' }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs {{ $user->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded transition-colors cursor-pointer">{{ $user->is_active ? 'Nonaktif' : 'Aktif' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" data-confirm="Reset password user {{ $user->name }}? Password baru akan dibuat otomatis." data-confirm-title="Reset Password" data-confirm-tone="warning" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs text-slate-500 hover:bg-slate-100 rounded transition-colors cursor-pointer">Reset PW</button>
                                    </form>
                                    @if($user->role !== 'admin' && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.login-as', $user) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs text-indigo-600 hover:bg-indigo-50 rounded transition-colors cursor-pointer">Login As</button>
                                    </form>
                                    @endif
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Hapus user {{ $user->name }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus User" data-confirm-tone="danger" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-xs text-red-500 hover:bg-red-50 rounded transition-colors cursor-pointer">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">Tidak ada data pengguna.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    <x-slot:modals>
        <div id="addUserModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Tambah Pengguna</h3>
                    <button onclick="closeModal('addUserModal')" aria-label="Tutup" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nama</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Password (opsional)</label>
                            <input type="text" name="password" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none" placeholder="Otomatis jika kosong">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Role</label>
                            <select name="role" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                                <option value="gudang">Gudang</option>
                                <option value="hr">HR</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-corpblue-500 hover:bg-corpblue-600 text-white rounded-lg font-medium cursor-pointer">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </x-slot:modals>
</x-layout>

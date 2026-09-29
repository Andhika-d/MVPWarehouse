<x-layout :title="__('Kelola Pengguna — THI2-WAREHOUSE')" :headerTitle="__('Kelola Pengguna')">
    <div class="space-y-6">

        {{-- Toolbar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari nama atau email...') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <select name="role" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">{{ __('Semua Peran') }}</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                    <option value="hr" {{ request('role') === 'hr' ? 'selected' : '' }}>{{ __('HR') }}</option>
                    <option value="director" {{ request('role') === 'director' ? 'selected' : '' }}>{{ __('Direktur') }}</option>
                    <option value="gudang" {{ request('role') === 'gudang' ? 'selected' : '' }}>{{ __('Gudang') }}</option>
                </select>
                @if(request()->filled('search') || (request()->filled('role') && request('role') !== 'all'))
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">{{ __('Reset') }}</a>
                @endif
                <button type="button" onclick="openModal('addUserModal')" class="btn btn--primary">{{ __('+ Tambah') }}</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Nama') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Peran') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Bahasa') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-status-badge domain="role" :status="$user->role" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('admin.users.locale', $user) }}" class="inline-flex justify-center">
                                    @csrf
                                    <select name="locale" onchange="this.form.submit()" aria-label="{{ __('Bahasa') }}" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-medium text-slate-600 focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none cursor-pointer">
                                        @foreach(config('language.supported') as $code => $label)
                                        <option value="{{ $code }}" @selected($user->locale === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <x-status-badge domain="account" :status="$user->is_active ? 'Aktif' : 'Nonaktif'" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" data-confirm="{{ $user->is_active ? __('Nonaktifkan pengguna :name?', ['name' => $user->name]) : __('Aktifkan pengguna :name?', ['name' => $user->name]) }}" data-confirm-title="{{ $user->is_active ? __('Nonaktifkan Pengguna') : __('Aktifkan Pengguna') }}" data-confirm-tone="{{ $user->is_active ? 'warning' : 'success' }}" class="inline">
                                        @csrf
                                        <button type="submit" class="action-link {{ $user->is_active ? 'action-link--warning' : 'action-link--success' }}">{{ $user->is_active ? __('Nonaktifkan') : __('Aktifkan') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" data-confirm="{{ __('Reset password pengguna :name? Password baru akan dibuat otomatis.', ['name' => $user->name]) }}" data-confirm-title="{{ __('Reset Password') }}" data-confirm-tone="warning" class="inline">
                                        @csrf
                                        <button type="submit" class="action-link action-link--neutral">{{ __('Reset Password') }}</button>
                                    </form>
                                    @if($user->role !== 'admin' && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.login-as', $user) }}" data-confirm="{{ __('Login sebagai :name? Anda akan berpindah ke sesi pengguna ini.', ['name' => $user->name]) }}" data-confirm-title="{{ __('Login Sebagai Pengguna') }}" data-confirm-tone="warning" class="inline">
                                        @csrf
                                        <button type="submit" class="action-link action-link--primary">{{ __('Login sebagai') }}</button>
                                    </form>
                                    @endif
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="{{ __('Hapus pengguna :name? Tindakan ini tidak dapat dibatalkan.', ['name' => $user->name]) }}" data-confirm-title="{{ __('Hapus Pengguna') }}" data-confirm-tone="danger" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-link action-link--danger">{{ __('Hapus') }}</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('Tidak ada data pengguna.') }}</td>
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
        <div id="addUserModal" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden" onclick="event.stopPropagation()">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 id="addUserModalTitle" class="text-base font-semibold text-slate-900">{{ __('Tambah Pengguna') }}</h3>
                    <button onclick="closeModal('addUserModal')" aria-label="Tutup" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Nama') }}</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Email') }}</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Password (opsional)') }}</label>
                            <input type="password" name="password" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none" placeholder="{{ __('Otomatis jika kosong') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Peran') }}</label>
                            <select name="role" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                                <option value="gudang">{{ __('Gudang') }}</option>
                                <option value="hr">{{ __('HR') }}</option>
                                <option value="director">{{ __('Direktur') }}</option>
                                <option value="admin">{{ __('Admin') }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('Bahasa') }}</label>
                        <select name="locale" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                            @foreach(config('language.supported') as $code => $label)
                            <option value="{{ $code }}" @selected($code === config('app.locale'))>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('addUserModal')" class="btn btn--secondary">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn--primary">{{ __('Simpan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </x-slot:modals>
</x-layout>

<x-layout :title="'Backup & Pemulihan — MVPWarehouse'" :headerTitle="'Backup & Pemulihan'">
    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Create Backup --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-2">Buat Backup Baru</h3>
            <p class="text-xs text-slate-500 mb-4">Membuat salinan database saat ini sebagai file .zip</p>
            <form method="POST" action="{{ route('admin.backups.create') }}" data-confirm="Buat backup database sekarang?" data-confirm-title="Buat Backup" data-confirm-tone="info" data-confirm-button="Buat Backup">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-corpblue-500 hover:bg-corpblue-600 text-white rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                    + Buat Backup
                </button>
            </form>
        </div>

        {{-- Backup List --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Daftar Backup</h3>
            </div>
            @forelse($backups as $backup)
            <div class="px-5 py-3 border-b border-slate-50 flex items-center justify-between gap-4 hover:bg-slate-50">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900 truncate font-mono">{{ $backup['name'] }}</p>
                    <p class="text-xs text-slate-400">{{ $backup['size'] }} &middot; {{ $backup['time'] }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.backups.download', $backup['name']) }}" class="px-3 py-1.5 text-xs text-corpblue-600 hover:bg-corpblue-50 rounded-lg font-medium transition-colors">Download</a>
                    <form method="POST" action="{{ route('admin.backups.restore', $backup['name']) }}" data-confirm="Restore database dari {{ $backup['name'] }}? Backup otomatis dibuat sebelum restore." data-confirm-title="Restore Database" data-confirm-tone="warning" data-confirm-button="Restore" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs text-amber-600 hover:bg-amber-50 rounded-lg font-medium transition-colors cursor-pointer">Restore</button>
                    </form>
                    <form method="POST" action="{{ route('admin.backups.destroy', $backup['name']) }}" data-confirm="Hapus backup {{ $backup['name'] }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Backup" data-confirm-tone="danger" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 text-xs text-red-500 hover:bg-red-50 rounded-lg font-medium transition-colors cursor-pointer">Hapus</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-5 py-12 text-center text-sm text-slate-400">
                Belum ada backup.
            </div>
            @endforelse
        </div>

    </div>
</x-layout>

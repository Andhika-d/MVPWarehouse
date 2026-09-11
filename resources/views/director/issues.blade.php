<x-layout :title="'Masalah & Analisis — MVPWarehouse'" :headerTitle="'Masalah & Analisis'">
    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Status</option>
                    <option value="Open" {{ ($status ?? '') === 'Open' ? 'selected' : '' }}>Open</option>
                    <option value="Dalam Tinjauan" {{ ($status ?? '') === 'Dalam Tinjauan' ? 'selected' : '' }}>Dalam Tinjauan</option>
                    <option value="Selesai" {{ ($status ?? '') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
                @if(request()->filled('status') && request('status') !== 'all')
                <a href="/director/issues" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        <div class="space-y-3">
            @forelse($issues as $issue)
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="text-sm font-semibold text-slate-900">{{ $issue->category }}</span>
                            <x-status-badge domain="severity" :status="$issue->severity" />
                            <x-status-badge domain="issue" :status="$issue->status" />
                        </div>
                        <p class="text-sm text-slate-600">{{ $issue->description }}</p>
                        @if($issue->stockRequest)
                        <p class="mt-1 text-xs text-slate-500">Terkait: {{ $issue->stockRequest->item_name }} — {{ $issue->stockRequest->user?->name ?? '—' }}</p>
                        @endif
                        @if($issue->note)
                        <p class="mt-1 text-xs italic text-corpblue-600">Catatan: {{ $issue->note }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">{{ $issue->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
                <svg class="mx-auto text-slate-300" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <p class="mt-3 text-sm text-slate-500">Belum ada masalah tercatat.</p>
            </div>
            @endforelse
        </div>

        @if($issues->hasPages())
        <div>{{ $issues->links() }}</div>
        @endif

    </div>
</x-layout>

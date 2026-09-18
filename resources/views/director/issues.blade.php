<x-layout :title="'Masalah & Analisis — THI2-WAREHOUSE'" :headerTitle="'Masalah & Analisis'">
    @php $isHr = auth()->user()?->role === 'hr'; @endphp
    <div class="space-y-6">

        {{-- Summary --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Issue Aktif</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['open'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-200 p-4">
                <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider">Peringatan</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ $summary['warning'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-red-200 p-4">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Kritis</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ $summary['critical'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-200 p-4">
                <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Riwayat Selesai</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $summary['resolved'] }}</p>
            </div>
        </div>

        @if(count($summary['by_rule']) > 0)
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Distribusi Issue Aktif per Tahap</p>
            <div class="flex flex-wrap gap-2">
                @foreach($summary['by_rule'] as $ruleKey => $total)
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-700">
                    {{ \App\Services\Monitoring\ProcessDelayDetector::RULE_LABELS[$ruleKey] ?? $ruleKey }}
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-corpblue-100 text-corpblue-700 text-xs font-semibold">{{ $total }}</span>
                </span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" data-auto-filter class="flex flex-col sm:flex-row gap-3">
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Status</option>
                    <option value="Open" {{ ($status ?? '') === 'Open' ? 'selected' : '' }}>Open</option>
                    <option value="Dalam Tinjauan" {{ ($status ?? '') === 'Dalam Tinjauan' ? 'selected' : '' }}>Dalam Tinjauan</option>
                    <option value="Selesai" {{ ($status ?? '') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
                <select name="severity" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="all">Semua Severity</option>
                    <option value="Peringatan" {{ ($severity ?? '') === 'Peringatan' ? 'selected' : '' }}>Peringatan</option>
                    <option value="Kritis" {{ ($severity ?? '') === 'Kritis' ? 'selected' : '' }}>Kritis</option>
                </select>
                @if((request()->filled('status') && request('status') !== 'all') || (request()->filled('severity') && request('severity') !== 'all'))
                <a href="{{ route($isHr ? 'hr.issues' : 'director.issues') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">Reset</a>
                @endif
            </form>
        </div>

        {{-- Issue list --}}
        <div class="space-y-3">
            @forelse($issues as $issue)
            @php
                $subject = $issue->subject;
                $startedAt = isset($issue->context['started_at']) ? \Carbon\Carbon::parse($issue->context['started_at']) : $issue->detected_at;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="text-sm font-semibold text-slate-900">{{ $issue->category }}</span>
                            <x-status-badge domain="severity" :status="$issue->severity" />
                            <x-status-badge domain="issue" :status="$issue->status" />
                            @if($issue->occurrence_count > 1)
                            <span class="text-xs text-slate-400">Terulang ×{{ $issue->occurrence_count }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-600">{{ $issue->description }}</p>

                        @if($subject instanceof \App\Models\StockRequest)
                        <p class="mt-1 text-xs text-slate-500">
                            Terkait: {{ $subject->item_name }} — {{ $subject->quantity }} {{ $subject->unit }} — {{ $subject->user?->name ?? '—' }}
                        </p>
                        <a href="{{ route($isHr ? 'hr.requests.show' : 'director.request-detail', $subject) }}" class="mt-1 inline-flex text-xs font-medium text-corpblue-600 hover:text-corpblue-800">
                            Lihat detail request →
                        </a>
                        @elseif($subject instanceof \App\Models\LocationChangeRequest)
                        <p class="mt-1 text-xs text-slate-500">
                            Terkait: {{ $subject->item?->display_name ?? '—' }} — perubahan lokasi
                            {{ $subject->fromLocation?->code ?? '—' }} → {{ $subject->toLocation?->code ?? '—' }}
                        </p>
                        @endif

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if($startedAt)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-500">Proses tertunda sejak&nbsp;<strong class="font-semibold whitespace-nowrap text-slate-700">{{ $startedAt->translatedFormat('d M Y, H:i') }}</strong></span>
                            @endif
                            @if($issue->detected_at)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-500">Deteksi pertama&nbsp;<strong class="font-semibold whitespace-nowrap text-slate-700">{{ $issue->detected_at->translatedFormat('d M Y') }}</strong></span>
                            @endif
                            @if($issue->isActive())
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-500">Terakhir terdeteksi&nbsp;<strong class="font-semibold whitespace-nowrap text-slate-700">{{ $issue->last_seen_at?->locale('id')->diffForHumans() }}</strong></span>
                            @elseif($issue->resolved_at)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-500">Selesai&nbsp;<strong class="font-semibold whitespace-nowrap text-slate-700">{{ $issue->resolved_at->translatedFormat('d M Y, H:i') }}</strong></span>
                            @endif
                        </div>
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
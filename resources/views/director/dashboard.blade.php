<x-layout :title="'Executive Monitoring — MVPWarehouse'" :headerTitle="'Executive Monitoring'">
    <div class="space-y-6">

        {{-- ═══ TOP: KPI Strip ═══ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Pending --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-corpblue-500 p-5">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Menunggu Proses</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ $pendingCount }}</p>
                @if($urgentPendingCount > 0)
                <p class="text-xs text-red-600 font-semibold mt-1">{{ $urgentPendingCount }} mendesak</p>
                @endif
            </div>

            {{-- Terlambat --}}
            <div class="bg-white rounded-2xl border {{ $overdueCount > 0 ? 'border-red-200 border-l-red-500' : 'border-slate-200 border-l-corpblue-500' }} border-l-4 p-5">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Terlambat (>3 Hari)</p>
                <p class="text-3xl font-extrabold {{ $overdueCount > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1.5">{{ $overdueCount }}</p>
                @if($overdueCount > 0)
                <p class="text-xs text-red-500 font-medium mt-1">perlu perhatian</p>
                @else
                <p class="text-xs text-emerald-600 font-medium mt-1">semua tepat waktu</p>
                @endif
            </div>

            {{-- Avg Processing --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-l-corpblue-500 border-l-4 p-5">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Rata-rata Selesai</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ $avgDays ?? '—' }} <span class="text-base font-semibold text-slate-400">hari</span></p>
                <p class="text-xs text-slate-400 mt-1">dari 30 hari terakhir</p>
            </div>

            {{-- Belum Diterima --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-l-corpblue-500 border-l-4 p-5">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Belum Diterima</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ $waitingReceiptCount }}</p>
                <p class="text-xs text-slate-400 mt-1">disetujui, menunggu gudang</p>
            </div>
        </div>

        {{-- ═══ MIDDLE ROW ═══ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Process Bottleneck --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5">Hambatan Proses (30 Hari)</h3>
                @php
                    $maxDelay = max($stageDelays['request_to_approval'], $stageDelays['approval_to_receipt'], 1);
                @endphp
                <div class="space-y-5">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-slate-700">Request → Approval</span>
                            <span class="text-sm font-bold {{ $stageDelays['request_to_approval'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $stageDelays['request_to_approval'] }} terlambat</span>
                        </div>
                             <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $stageDelays['request_to_approval'] > 0 ? 'bg-amber-400' : 'bg-corpblue-500' }}" style="width: {{ ($stageDelays['request_to_approval'] / $maxDelay) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-slate-700">Approval → Penerimaan</span>
                            <span class="text-sm font-bold {{ $stageDelays['approval_to_receipt'] > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $stageDelays['approval_to_receipt'] }} terlambat</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $stageDelays['approval_to_receipt'] > 0 ? 'bg-red-400' : 'bg-corpblue-500' }}" style="width: {{ ($stageDelays['approval_to_receipt'] / $maxDelay) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
                @if($stageDelays['approval_to_receipt'] > $stageDelays['request_to_approval'])
                <p class="text-xs text-red-500 font-medium mt-5 bg-red-50 rounded-lg px-3 py-2">Hambatan terbesar: waktu antara approval dan penerimaan barang di gudang.</p>
                @elseif($stageDelays['request_to_approval'] > 0)
                <p class="text-xs text-amber-600 font-medium mt-5 bg-amber-50 rounded-lg px-3 py-2">Hambatan terbesar: waktu persetujuan request oleh HR.</p>
                @else
                <p class="text-xs text-corpblue-700 font-medium mt-5 bg-corpblue-50 rounded-lg px-3 py-2">Semua proses berjalan lancar dalam 30 hari terakhir.</p>
                @endif
            </div>

            {{-- Warehouse Summary --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:col-span-1">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Ringkasan Gudang</h3>
                        <p class="text-xs text-slate-400 mt-1">Kondisi inventaris saat ini</p>
                    </div>
                    <svg class="text-slate-400 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M8 10h.01M12 10h.01M16 10h.01M8 13h.01M12 13h.01M16 13h.01"/></svg>
                </div>
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                    <div>
                        <p class="text-xl font-extrabold text-slate-900">{{ number_format($warehouseSummary['item_types']) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Jenis barang</p>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold text-slate-900">{{ number_format($warehouseSummary['total_stock']) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Total stok</p>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold text-slate-900">{{ $warehouseSummary['occupied_locations'] }} / {{ $warehouseSummary['total_locations'] }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Lokasi terisi</p>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold text-slate-900">{{ $warehouseSummary['total_locations'] - $warehouseSummary['occupied_locations'] }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Lokasi kosong</p>
                    </div>
                </div>
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-600">Kapasitas gudang</span>
                        <span class="text-xs font-bold text-slate-800">{{ $warehouseSummary['capacity_percentage'] }}%</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-slate-700 rounded-full" style="width: {{ $warehouseSummary['capacity_percentage'] }}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-5 pt-4 border-t border-slate-100">
                    <div>
                        <p class="text-sm font-bold text-slate-800">+{{ number_format($warehouseSummary['incoming_week']) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Masuk 7 hari</p>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">-{{ number_format($warehouseSummary['outgoing_week']) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Keluar 7 hari</p>
                    </div>
                </div>
            </div>

            {{-- Recent Critical Events --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:col-span-1">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5">Kejadian 7 Hari Terakhir</h3>
                <div class="space-y-3">
                    @forelse($recentMovements as $mov)
                    <div class="flex items-start gap-3">
                             <div class="w-7 h-7 rounded-full {{ $mov->type === 'IN' ? 'bg-corpblue-100' : ($mov->type === 'OUT' ? 'bg-red-100' : 'bg-slate-100') }} flex items-center justify-center shrink-0 mt-0.5">
                            @if($mov->type === 'IN')
                            <svg class="text-corpblue-600" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16l-4 4m4-4l4 4"/></svg>
                            @elseif($mov->type === 'OUT')
                            <svg class="text-red-600" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 0l-4 4m4-4l4 4"/></svg>
                            @else
                            <svg class="text-slate-600" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-6-6h12"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-700 leading-snug">
                                <span class="font-semibold">{{ $mov->user?->name ?? '—' }}</span>
                                {{ $mov->type === 'IN' ? 'menerima' : ($mov->type === 'OUT' ? 'mengeluarkan' : 'menyesuaikan') }}
                                <span class="font-semibold">{{ $mov->quantity }} {{ $mov->unit }}</span>
                                {{ strtolower($mov->item?->name ?? 'barang') }}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $mov->occurred_at?->diffForHumans() ?? '—' }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada aktivitas minggu ini.</p>
                    @endforelse

                    @forelse($recentLocationChanges as $lc)
                    <div class="flex items-start gap-3">
                         <div class="w-7 h-7 rounded-full bg-corpblue-100 flex items-center justify-center shrink-0 mt-0.5">
                             <svg class="text-corpblue-600" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            @if($lc->target_sub_location !== null)
                            <p class="text-sm text-slate-700 leading-snug">
                                <span class="font-semibold">{{ $lc->requestedBy?->name ?? '—' }}</span>
                                ajukan pindah
                                <span class="font-semibold">{{ $lc->item?->name ?? '—' }}</span>
                                <span class="text-xs text-slate-400">{{ $lc->fromLocation?->code }} · {{ $lc->from_sub_location ?? '—' }} → {{ $lc->toLocation?->code }} · {{ $lc->target_sub_location ?? '—' }}</span>
                            </p>
                            @else
                            <p class="text-sm text-slate-700 leading-snug">
                                <span class="font-semibold">{{ $lc->requestedBy?->name ?? '—' }}</span>
                                ajukan pindah
                                <span class="font-semibold">{{ $lc->item?->name ?? '—' }}</span>
                                <span class="text-xs text-slate-400">{{ $lc->fromLocation?->code }} → {{ $lc->toLocation?->code }}</span>
                            </p>
                            @endif
                            <p class="text-xs text-slate-400 mt-0.5">{{ $lc->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    @endforelse

                    @if($recentMovements->isEmpty() && $recentLocationChanges->isEmpty())
                    <div class="flex items-center justify-center h-24">
                        <p class="text-xs text-slate-400">Belum ada kejadian minggu ini.</p>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ═══ NEEDS ATTENTION ═══ --}}
        @if($overdueRequests->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Overdue Requests --}}
            @if($overdueRequests->count() > 0)
            <div class="bg-white rounded-2xl border border-red-200 overflow-hidden">
                <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                        <h3 class="text-sm font-bold text-red-800">Terlambat (> 3 Hari)</h3>
                    </div>
                    <a href="{{ route('director.requests', ['status' => 'all']) }}" class="text-xs text-red-600 hover:text-red-800 font-medium">Lihat Semua</a>
                </div>
                <div class="divide-y divide-red-50">
                    @foreach($overdueRequests as $item)
                    <a href="{{ route('director.request-detail', $item['request']) }}" class="block px-6 py-3.5 hover:bg-red-50/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900 truncate">{{ $item['request']->item_name }}</span>
                                    @if($item['is_urgent'])
                                    <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 text-red-700 text-[10px] font-bold uppercase">Mendesak</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $item['request']->user?->name ?? '—' }} · {{ $item['request']->status }} · {{ $item['request']->quantity }} {{ $item['request']->unit }}</p>
                            </div>
                            <div class="shrink-0 ml-4 text-right">
                                <span class="text-lg font-extrabold text-red-600">{{ $item['days_open'] }}</span>
                                <span class="text-[10px] text-red-500 font-semibold block -mt-0.5">hari</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
        @endif

    </div>
</x-layout>

<x-layout :title="'Executive Monitoring — MVPWarehouse'" :headerTitle="'Executive Monitoring'">
    <div class="space-y-6">

        {{-- ═══ TOP: KPI Strip ═══ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Pending --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-corpblue-500 p-5">
                <p class="ui-heading">Menunggu Proses</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ $pendingCount }}</p>
                @if($urgentPendingCount > 0)
                <p class="text-xs text-red-600 font-semibold mt-1">{{ $urgentPendingCount }} mendesak</p>
                @endif
            </div>

            {{-- Terlambat --}}
            <div class="bg-white rounded-2xl border {{ $overdueCount > 0 ? 'border-red-200 border-l-red-500' : 'border-slate-200 border-l-corpblue-500' }} border-l-4 p-5">
                <p class="ui-heading">Terlambat (&gt;3 Hari)</p>
                <p class="text-3xl font-extrabold {{ $overdueCount > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1.5">{{ $overdueCount }}</p>
                @if($overdueCount > 0)
                <p class="text-xs text-red-500 font-medium mt-1">proses aktif tertahan</p>
                @else
                <p class="text-xs text-emerald-600 font-medium mt-1">semua tepat waktu</p>
                @endif
            </div>

            {{-- Avg Processing --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-l-corpblue-500 border-l-4 p-5">
                <p class="ui-heading">Rata-rata Selesai</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ $avgDays ?? '—' }} <span class="text-base font-semibold text-slate-400">hari</span></p>
                <p class="mt-1 text-xs text-slate-500">dari 30 hari terakhir</p>
            </div>

            {{-- Belum Diterima --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-l-corpblue-500 border-l-4 p-5">
                <p class="ui-heading">Belum Diterima</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ $waitingReceiptCount }}</p>
                <p class="mt-1 text-xs text-slate-500">disetujui, menunggu gudang</p>
            </div>
        </div>

        {{-- ═══ MIDDLE ROW ═══ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:h-[340px]">

            {{-- Process Bottleneck --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:h-full flex flex-col min-h-0">
                <div class="flex items-center justify-between gap-3 mb-4 shrink-0">
                    <div>
                        <h3 class="ui-heading">Hambatan Proses (&gt;3 Hari)</h3>
                        <p class="mt-1 text-xs text-slate-500">Proses aktif dan riwayat 30 hari</p>
                    </div>
                    <span class="rounded-full {{ $overdueCount > 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-xs font-bold">{{ $overdueCount }} aktif</span>
                </div>
                @php
                    $maxActiveDelay = max(array_merge(array_values($processBottlenecks['active_counts']), [1]));
                    $maxHistoricalDelay = max(array_merge(array_values($processBottlenecks['historical_counts']), [1]));
                @endphp
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain pr-2">
                    <div>
                        <h4 class="ui-heading mb-3">Proses Aktif</h4>
                        <div class="space-y-3">
                            @foreach($processBottlenecks['labels'] as $key => $label)
                            @php $count = $processBottlenecks['active_counts'][$key]; @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <span class="text-xs font-medium text-slate-600">{{ $label }}</span>
                                    <span class="text-xs font-bold {{ $count > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $count }} request</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $count > 0 ? 'bg-red-400' : 'bg-slate-300' }}" style="width: {{ ($count / $maxActiveDelay) * 100 }}%"></div></div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @if($processBottlenecks['active_items']->isNotEmpty())
                    <div class="border-t border-slate-100 pt-4">
                        <div class="mb-2 flex items-center justify-between"><h4 class="ui-heading">Detail Aktif</h4><a href="{{ route('director.requests', ['status' => 'all']) }}" class="text-xs font-semibold text-corpblue-600 hover:text-corpblue-800">Lihat Semua</a></div>
                        <div class="divide-y divide-slate-100">
                            @foreach($processBottlenecks['active_items'] as $item)
                            <a href="{{ route('director.request-detail', $item['request']) }}" class="flex items-center justify-between gap-3 py-2.5 hover:bg-slate-50">
                                <div class="min-w-0"><p class="truncate text-xs font-semibold text-slate-800">{{ $item['request']->item?->name ?? $item['request']->item_name ?? 'Barang' }}</p><p class="mt-0.5 truncate text-xs text-slate-500">{{ $item['stage_label'] }} · {{ $item['request']->user?->name ?? '—' }}</p></div>
                                <span class="shrink-0 text-xs font-bold text-red-600">{{ $item['days_open'] }} hari</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="border-t border-slate-100 pt-4">
                        <div class="mb-3 flex items-center justify-between gap-3"><h4 class="ui-heading">Riwayat 30 Hari</h4><span class="text-xs text-slate-500">{{ $processBottlenecks['historical_delayed_requests'] }}/{{ $processBottlenecks['historical_total'] }} request pernah terlambat</span></div>
                        <div class="space-y-3">
                            @foreach($processBottlenecks['labels'] as $key => $label)
                            @php $count = $processBottlenecks['historical_counts'][$key]; @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3"><span class="text-xs font-medium text-slate-600">{{ $label }}</span><span class="text-xs font-bold {{ $count > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $count }} kasus</span></div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $count > 0 ? 'bg-amber-400' : 'bg-slate-300' }}" style="width: {{ ($count / $maxHistoricalDelay) * 100 }}%"></div></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Warehouse Summary --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:col-span-1 lg:h-full overflow-y-auto">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="ui-heading">Ringkasan Gudang</h3>
                        <p class="mt-1 text-xs text-slate-500">Kondisi inventaris saat ini</p>
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
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:col-span-1 lg:h-full flex flex-col min-h-0">
                <h3 class="ui-heading mb-5">Kejadian 7 Hari Terakhir</h3>
                <div class="space-y-3 min-h-0 flex-1 overflow-y-auto overscroll-contain pr-2">
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

    </div>
</x-layout>

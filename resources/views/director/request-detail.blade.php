<x-layout :title="'Detail Request — MVPWarehouse'" :headerTitle="'Detail Permintaan'">
    <div class="space-y-6">

        <a href="{{ route('director.requests') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-corpblue-600 font-medium transition-colors">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ═══ Left: Info + Duration ═══ --}}
            <div class="lg:col-span-1 space-y-5">

                {{-- Status Card --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-5">
                        @if($request->status === 'Diterima Penuh')
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="text-emerald-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        @elseif($request->status === 'Ditolak')
                        <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="text-red-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        @elseif(in_array($request->status, ['Menunggu Review', 'Pending']))
                        <div class="w-11 h-11 rounded-xl bg-amber-100 flex items-center justify-center">
                            <svg class="text-amber-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        @elseif(in_array($request->status, ['Ditutup Sebagian', 'Dibatalkan']))
                        <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center">
                            <svg class="text-slate-500" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        @else
                        <div class="w-11 h-11 rounded-xl bg-corpblue-100 flex items-center justify-center">
                            <svg class="text-corpblue-600" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        @endif
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $request->item_name }}</h2>
                            <p class="text-xs text-slate-400">{{ $request->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">Peminta</span>
                            <span class="text-sm font-medium text-slate-700">{{ $request->user?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">Jumlah</span>
                            <span class="text-sm font-medium text-slate-700">{{ $request->quantity }} {{ $request->unit }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">Diterima</span>
                            <span class="text-sm font-medium {{ $request->received_quantity >= $request->quantity ? 'text-emerald-600' : 'text-amber-600' }}">{{ $request->received_quantity }} {{ $request->unit }}</span>
                        </div>
                        @if($request->received_quantity < $request->quantity)
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">{{ $request->isClosed() ? 'Sisa (ditutup)' : 'Sisa' }}</span>
                            <span class="text-sm font-semibold {{ $request->isClosed() ? 'text-slate-400' : 'text-red-600' }}">{{ $request->quantity - $request->received_quantity }} {{ $request->unit }}</span>
                        </div>
                        @endif
                        @if($request->isClosed() && $request->closed_at)
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">Ditutup oleh</span>
                            <span class="text-sm font-medium text-slate-700">{{ $request->closedBy?->name ?? '—' }} · {{ $request->closed_at->format('d M Y, H:i') }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">Prioritas</span>
                            @if($request->priority === 'Mendesak')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Mendesak</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">Normal</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs text-slate-400">Status</span>
                            @if($request->status === 'Menunggu Review' || $request->status === 'Pending')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">{{ $request->status }}</span>
                            @elseif($request->status === 'Disetujui')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-corpblue-50 text-corpblue-700 text-xs font-semibold">{{ $request->status }}</span>
                            @elseif($request->status === 'Sebagian Diterima')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $request->status }}</span>
                            @elseif($request->status === 'Diterima Penuh')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">{{ $request->status }}</span>
                            @elseif($request->status === 'Ditolak')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">{{ $request->status }}</span>
                            @elseif($request->status === 'Ditutup Sebagian')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ $request->status }}</span>
                            @elseif($request->status === 'Dibatalkan')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-xs font-semibold">{{ $request->status }}</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">{{ $request->status }}</span>
                            @endif
                        </div>
                        <div class="py-2">
                            <span class="text-xs text-slate-400 block mb-1">Alasan</span>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $request->reason ?: '—' }}</p>
                        </div>
                        @if($request->isClosed() && $request->close_note)
                        <div class="py-2">
                            <span class="text-xs text-slate-400 block mb-1">Alasan Penutupan</span>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $request->close_note }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Duration Analysis --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Analisis Durasi</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-500">Request → Approval</span>
                                <span class="text-sm font-bold {{ match($durations['request_to_approval_state']) { 'completed' => 'text-slate-900', 'ongoing' => 'text-amber-600', default => 'text-slate-300' } }}">{{ $durations['request_to_approval'] ?? '—' }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $durations['request_to_approval_state'] === 'ongoing' ? 'bg-amber-400' : 'bg-corpblue-400' }}" style="width: {{ $durations['request_to_approval'] ? '100' : '0' }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-500">Approval → Penerimaan Awal</span>
                                <span class="text-sm font-bold {{ $durations['approval_to_first_receipt'] ? 'text-slate-900' : 'text-slate-300' }}">{{ $durations['approval_to_first_receipt'] ?? '—' }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-400 rounded-full" style="width: {{ $durations['approval_to_first_receipt'] ? '100' : '0' }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1 gap-3">
                                <span class="text-xs text-slate-500">Durasi Pemenuhan</span>
                                <span class="text-sm font-bold text-right {{ match($durations['fulfillment_state']) { 'completed' => 'text-emerald-600', 'ongoing' => 'text-amber-600', 'closed' => 'text-slate-600', default => 'text-slate-300' } }}">{{ $durations['fulfillment_duration'] ?? '—' }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $durations['fulfillment_state'] === 'completed' ? 'bg-emerald-500' : ($durations['fulfillment_state'] === 'ongoing' ? 'bg-amber-400' : 'bg-slate-400') }}" style="width: {{ $durations['fulfillment_duration'] ? '100' : '0' }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-500">Request → Selesai</span>
                                <span class="text-sm font-bold {{ $durations['request_to_complete'] ? 'text-slate-900' : 'text-slate-300' }}">{{ $durations['request_to_complete'] ?? '—' }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-400 rounded-full" style="width: {{ $durations['request_to_complete'] ? '100' : '0' }}%"></div>
                            </div>
                        </div>
                        @if($durations['receipt_count'] > 0)
                        @php
                            $receiptProgress = $durations['requested_quantity'] > 0
                                ? min(100, ($durations['received_quantity'] / $durations['requested_quantity']) * 100)
                                : 0;
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold text-slate-600">{{ $durations['receipt_count'] }} tahap penerimaan</span>
                                <span class="text-xs font-bold {{ $receiptProgress >= 100 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $durations['received_quantity'] }}/{{ $durations['requested_quantity'] }} {{ $durations['unit'] }} diterima</span>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full {{ $receiptProgress >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $receiptProgress }}%"></div>
                            </div>
                        </div>
                        @endif
                        @if($durations['request_to_close'])
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-500">Request → Penutupan</span>
                                <span class="text-sm font-bold text-slate-900">{{ $durations['request_to_close'] }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-slate-400 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                        @endif
                        @if($durations['total_calendar_days'] !== null)
                        <div class="pt-3 mt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-600">Total Hari Kalender</span>
                                <span class="text-xl font-extrabold text-slate-900">{{ $durations['total_calendar_days'] }} <span class="text-xs font-semibold text-slate-400">hari</span></span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══ Right: Timeline ═══ --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Timeline</h3>

                    @if($timeline->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-8">Belum ada aktivitas.</p>
                    @else
                    <div class="relative">
                        {{-- Vertical Line --}}
                        <div class="absolute left-[15px] top-3 bottom-3 w-px bg-slate-200"></div>

                        <div class="space-y-1">
                            @foreach($timeline as $i => $event)
                            @php
                                $isLast = $i === $timeline->count() - 1;
                                $colorMap = [
                                    'emerald' => ['bg' => 'bg-emerald-500', 'light' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
                                    'red' => ['bg' => 'bg-red-500', 'light' => 'bg-red-50', 'text' => 'text-red-600'],
                                    'amber' => ['bg' => 'bg-amber-500', 'light' => 'bg-amber-50', 'text' => 'text-amber-600'],
                                    'blue' => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-50', 'text' => 'text-blue-600'],
                                    'slate' => ['bg' => 'bg-slate-400', 'light' => 'bg-slate-50', 'text' => 'text-slate-500'],
                                ];
                                $c = $colorMap[$event['color']] ?? $colorMap['slate'];
                            @endphp
                            <div class="relative flex items-start gap-4 pl-0 py-2">
                                {{-- Dot --}}
                                <div class="relative z-10 w-[30px] flex items-center justify-center shrink-0">
                                    <div class="w-3 h-3 rounded-full {{ $c['bg'] }} ring-4 ring-white"></div>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0 {{ !$isLast ? 'pb-3' : '' }}">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-slate-900">{{ $event['label'] }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium uppercase">{{ $event['time']?->format('d M H:i') ?? '—' }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">oleh <span class="font-medium text-slate-600">{{ $event['user'] }}</span></p>
                                    @if(isset($event['decision']))
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed whitespace-pre-line">{{ $event['decision'] }} oleh {{ $event['processor'] }}</p>
                                    @endif
                                    @if($event['detail'])
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed whitespace-pre-line">{{ $event['detail'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</x-layout>

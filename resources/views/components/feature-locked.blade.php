@props([
    'title' => 'Fitur Sedang Dikembangkan',
    'description' => null,
    'compact' => false,
])

@php
    $roadmap = [
        'Minimum Stock',
        'Warning Stock',
        'Dashboard Statistik',
        'Analisis Riwayat Pembelian',
        'Rekomendasi Pembelian',
    ];
@endphp

@if($compact)
    <div class="bg-white rounded-xl border border-dashed border-slate-300 p-6">
        <div class="flex items-start space-x-3">
            <div class="w-10 h-10 bg-slate-100 text-slate-400 rounded-lg flex items-center justify-center shrink-0">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <h3 class="text-sm font-bold text-slate-700">{{ $title }}</h3>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-[10px] font-bold uppercase tracking-wide">v1.1</span>
                </div>
                <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                    {{ $description ?? 'Fitur ini sedang dalam tahap pengembangan dan akan tersedia pada rilis berikutnya.' }}
                </p>
            </div>
        </div>
    </div>
@else
    <div class="bg-white rounded-xl border border-dashed border-slate-300 shadow-sm overflow-hidden">
        <div class="p-6 md:p-10 lg:p-14 flex flex-col items-center text-center max-w-2xl mx-auto">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mb-6">
                <svg width="30" height="30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>

            <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed max-w-lg">
                {{ $description ?? 'Fitur ini tidak termasuk dalam cakupan sistem versi 1.0. Modul sedang dalam tahap pengembangan dan akan dirilis pada versi berikutnya.' }}
            </p>

            <div class="mt-6 w-full">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">Roadmap Rilis v1.1</p>
                <div class="flex flex-wrap justify-center gap-2">
                    @foreach($roadmap as $item)
                        <span class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600">{{ $item }}</span>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 bg-blue-50/60 border border-blue-100 rounded-lg px-4 py-3 text-xs text-blue-700 leading-relaxed max-w-lg">
                Modul ini akan aktif setelah sistem versi 1.0 berjalan stabil dan seluruh proses permintaan barang terpantau penuh di sistem.
            </div>
        </div>
    </div>
@endif

<x-layout :title="'Timeline Aktivitas — MVPWarehouse'" :headerTitle="'Timeline Aktivitas Perusahaan'">
    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari aktivitas..." class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors cursor-pointer">Filter</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5">Semua Aktivitas</h3>
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-px bg-slate-200"></div>
                <div class="space-y-4">
                    @forelse($paginated as $event)
                    <div class="relative flex items-start gap-4 pl-4">
                        @if(in_array($event['icon'], ['approved', 'IN']))
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 relative z-10">
                            <svg class="text-emerald-600" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        @elseif(in_array($event['icon'], ['rejected', 'OUT']))
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0 relative z-10">
                            <svg class="text-red-600" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6"/></svg>
                        </div>
                        @elseif(in_array($event['icon'], ['ADJUSTMENT']))
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0 relative z-10">
                            <svg class="text-amber-600" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                        @elseif($event['icon'] === 'location')
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 relative z-10">
                            <svg class="text-indigo-600" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        @else
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0 relative z-10">
                            <svg class="text-slate-500" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        @endif

                        <div class="flex-1 min-w-0 pb-2 border-b border-slate-50">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-slate-900">{{ $event['type'] }}</span>
                                <span class="text-xs text-slate-400">oleh {{ $event['user'] }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $event['detail'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $event['time'] ? \Carbon\Carbon::parse($event['time'])->format('d M Y, H:i') : '—' }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="pl-4 text-sm text-slate-400">Tidak ada aktivitas.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-layout>

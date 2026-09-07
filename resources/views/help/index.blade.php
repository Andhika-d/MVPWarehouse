<x-layout>
    <x-slot:title>Bantuan — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Bantuan Sistem</x-slot:headerTitle>
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-widest text-corpblue-500">User Guidance System</p><h2 class="mt-2 text-2xl font-bold text-slate-900">Panduan penggunaan</h2><p class="mt-1 text-sm text-slate-500">Ini adalah halaman panduan yang akan memperkenalkan fitur fitur utama pada system</p></div><button type="button" onclick="history.back()" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:border-corpblue-300 hover:text-corpblue-700">← Kembali</button></div>
        @if($guides->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-400">Belum ada panduan yang tersedia.</div>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($guides as $guide)
                    <a href="{{ route('help.show', $guide) }}" class="group rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-corpblue-300 hover:shadow-lg">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-corpblue-500">{{ $guide->category }}</span>
                        <h3 class="mt-3 text-base font-bold text-slate-900 group-hover:text-corpblue-600">{{ $guide->title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $guide->description ?: 'Panduan singkat penggunaan fitur.' }}</p>
                        <span class="mt-5 inline-flex text-xs font-semibold text-corpblue-600">Lihat panduan →</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>

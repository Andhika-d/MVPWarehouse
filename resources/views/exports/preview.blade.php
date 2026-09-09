<x-layout>
    <x-slot:title>Preview {{ $title }} — MVPWarehouse</x-slot:title>
    <x-slot:headerTitle>Preview Export</x-slot:headerTitle>

    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ $backUrl }}" class="text-sm font-semibold text-corpblue-600 hover:text-corpblue-800">&larr; Kembali</a>
                <h2 class="mt-3 text-xl font-bold text-slate-900">{{ $title }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Menampilkan {{ min($totalRows, 100) }} dari {{ $totalRows }} baris.
                    @if($totalRows > 100) File unduhan tetap berisi seluruh data. @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($downloads as $download)
                    <a href="{{ $download['url'] }}" class="inline-flex min-h-10 items-center rounded-lg px-4 py-2 text-sm font-semibold text-white {{ $download['format'] === 'PDF' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        Unduh {{ $download['format'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach($columns as $column)
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($rows as $row)
                            <tr class="align-top hover:bg-slate-50/70">
                                @foreach(array_values($row) as $value)
                                    <td class="max-w-sm whitespace-normal px-4 py-3 text-slate-600">{{ $value ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>

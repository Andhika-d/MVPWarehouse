<x-layout :title="'Audit Log — MVPWarehouse'" :headerTitle="'Audit Log Global'">
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari detail atau aksi..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                </div>
                <select name="action" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-corpblue-500 focus:border-corpblue-500 outline-none">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors cursor-pointer">Filter</button>
                <a href="{{ route('admin.audit.export-excel', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-medium transition-colors">Export Excel</a>
            </form>
        </div>

        {{-- Log Table --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Waktu</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">User</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Aksi</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-mono">{{ $log->action }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs max-w-md truncate">{{ $log->details }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-400">Belum ada audit log.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
            @endif
        </div>

    </div>
</x-layout>

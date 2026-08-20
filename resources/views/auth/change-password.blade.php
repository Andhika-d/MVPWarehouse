<x-layout>
    <x-slot:title>Ubah Password - CorpLogistics</x-slot:title>
    <x-slot:headerTitle>Ubah Password</x-slot:headerTitle>

    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <p class="text-sm text-slate-600 mb-6">
                Password sementara Anda wajib diganti sebelum dapat menggunakan aplikasi.
            </p>

            <form method="POST" action="/ubah-password" class="space-y-4">
                @csrf

                <div>
                    <label for="current_password" class="block text-sm font-semibold text-slate-700">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">Password Baru</label>
                    <input type="password" id="password" name="password" required
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Konfirmasi Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white hover:bg-blue-700 transition-all min-h-[44px]">
                    Simpan Password
                </button>
            </form>
        </div>
    </div>
</x-layout>

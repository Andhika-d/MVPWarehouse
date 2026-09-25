<div class="toolbar">
    <a href="{{ $backUrl }}">Kembali</a>
    <a href="{{ request()->fullUrlWithQuery(['orientation' => 'landscape']) }}" class="{{ $orientation === 'landscape' ? 'active' : '' }}">Landscape</a>
    <a href="{{ request()->fullUrlWithQuery(['orientation' => 'portrait']) }}" class="{{ $orientation === 'portrait' ? 'active' : '' }}">Portrait</a>
    <button type="button" onclick="window.print()">Cetak A4 {{ ucfirst($orientation) }}</button>
</div>
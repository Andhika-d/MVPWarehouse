<table class="doc-header">
    <tr>
        <td class="doc-header-title">
            <h1>{{ $title }}</h1>
            @if($subtitle)<div class="subtitle">{{ $subtitle }}</div>@endif
        </td>
        <td class="doc-header-meta">
            <div class="meta"><strong>{{ $printedAt->translatedFormat('d F Y, H:i') }}</strong><br>Dicetak oleh {{ $printedBy }} ({{ $printedRole }})</div>
            @include('prints.approval-grid')
        </td>
    </tr>
</table>
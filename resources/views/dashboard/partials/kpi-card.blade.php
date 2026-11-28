@php
    $tom = $kpi['tom'] ?? 'info';
    $url = $kpi['url'] ?? null;
    $ajuda = trim((string) ($kpi['ajuda'] ?? ''));
    $detalhe = $kpi['detalhe'] ?? null;
    $detalheExtra = $kpi['detalhe_extra'] ?? null;
    $rotulo = (string) ($kpi['rotulo'] ?? '');
    $externo = is_string($url)
        && str_starts_with($url, 'http')
        && ! str_starts_with($url, url('/'));
@endphp
<div class="kpi {{ $tom }}">
    @if($ajuda !== '')
        <button type="button"
                class="kpi-ajuda"
                aria-label="Como é calculado: {{ $rotulo !== '' ? $rotulo : 'indicador' }}"
                aria-expanded="false"
                aria-haspopup="true"
                data-kpi-ajuda
                data-kpi-rotulo="{{ $rotulo }}"
                data-ajuda="{{ e($ajuda) }}">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="kpi-ajuda-pop" hidden>{{ $ajuda }}</span>
        </button>
    @endif

    @if(!empty($url))
        <a class="kpi-corpo"
           href="{{ $url }}"
           @if($externo) target="_blank" rel="noopener" @endif>
            <div class="kpi-ico"><i class="fa {{ $kpi['icone'] ?? 'fa-info-circle' }}"></i></div>
            <span>{{ $rotulo }}</span>
            <strong>{{ $kpi['valor'] ?? '—' }}</strong>
            @if(!empty($detalhe))<small>{{ $detalhe }}</small>@endif
            @if(!empty($detalheExtra))<small class="kpi-extra">{{ $detalheExtra }}</small>@endif
        </a>
    @else
        <div class="kpi-corpo">
            <div class="kpi-ico"><i class="fa {{ $kpi['icone'] ?? 'fa-info-circle' }}"></i></div>
            <span>{{ $rotulo }}</span>
            <strong>{{ $kpi['valor'] ?? '—' }}</strong>
            @if(!empty($detalhe))<small>{{ $detalhe }}</small>@endif
            @if(!empty($detalheExtra))<small class="kpi-extra">{{ $detalheExtra }}</small>@endif
        </div>
    @endif
</div>

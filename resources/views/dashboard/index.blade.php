@extends('dashboard.layout')

@section('topo-titulo', 'Meu painel')

@section('conteudo')
@php
    $notificacoes = $dados['notificacoes'];
    $atencoes = $dados['atencoes'];
    $atalhos = $dados['atalhos'];
    $ctx = $dados['contexto'] ?? [];
    $grupos = $dados['kpi_grupos'] ?? [];
    $kpisFlat = array_values(array_filter(
        $dados['kpis'] ?? [],
        static fn (array $k): bool => in_array($k['grupo'] ?? '', ['rede', 'qualidade'], true)
    ));
    $rodapeOperacao = $dados['rodape_operacao'] ?? [];
    $naoLidas = (int) ($dados['nao_lidas'] ?? 0);

    // Atenções reais (sem o eco de "X notificações não lidas")
    $atencoesReais = array_values(array_filter(
        $atencoes,
        static fn (array $a): bool => ! str_contains(mb_strtolower($a['titulo'] ?? ''), 'notificação')
            && ! str_contains(mb_strtolower($a['titulo'] ?? ''), 'notificacao')
            && ($a['tom'] ?? '') !== 'ok'
    ));

    $caixaItens = [];
    $vistos = [];
    foreach ($atencoesReais as $a) {
        // MFA/segurança: só no lembrete junto ao bem-vindo — nunca na Caixa do dia.
        if (($a['filtro'] ?? '') === 'seguranca' || ($a['tipo'] ?? '') === 'seguranca') {
            continue;
        }
        $chave = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) ($a['titulo'] ?? ''))));
        $chaveCurta = mb_substr($chave, 0, 80);
        if ($chaveCurta !== '' && isset($vistos[$chaveCurta])) {
            continue;
        }
        if ($chaveCurta !== '') {
            $vistos[$chaveCurta] = true;
        }
        $caixaItens[] = [
            'tipo' => (string) ($a['tipo'] ?? 'atencao'),
            'filtro' => (string) ($a['filtro'] ?? 'atencao'),
            'titulo' => $a['titulo'],
            'detalhe' => $a['detalhe'] ?? '',
            'tom' => $a['tom'] ?? 'info',
            'icone' => $a['icone'] ?? 'fa-exclamation-circle',
            'url' => $a['url'] ?? null,
            'cta' => 'Abrir',
        ];
    }
    foreach ($notificacoes as $n) {
        $chave = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) ($n['texto'] ?? ''))));
        $chaveCurta = mb_substr($chave, 0, 80);
        if ($chaveCurta === '' || isset($vistos[$chaveCurta])) {
            continue;
        }
        $vistos[$chaveCurta] = true;
        $caixaItens[] = [
            'tipo' => 'notif',
            'filtro' => 'notif',
            'titulo' => $n['texto'],
            'detalhe' => $n['quando'] ?? '',
            'tom' => ! empty($n['lida']) ? 'ok' : 'alerta',
            'icone' => ! empty($n['lida']) ? 'fa-envelope-open' : 'fa-envelope',
            'url' => $n['link'] ?? url('/notificacoes'),
            'cta' => 'Ver',
        ];
    }

    $seguranca = $dados['seguranca'] ?? [];
    $avisosSeguranca = $seguranca['avisos'] ?? [];
    $periodo = (string) ($ctx['periodo'] ?? 'manha');
    $periodoIcone = match ($periodo) {
        'noite', 'madrugada' => 'fa-moon-o',
        'tarde' => 'fa-sun-o',
        default => 'fa-sun-o',
    };
    $periodoRotulo = match ($periodo) {
        'madrugada' => 'Madrugada',
        'tarde' => 'Tarde',
        'noite' => 'Noite',
        default => 'Manhã',
    };
@endphp
<div class="miolo-inner painel-v2">
    @php
        $qModulos = count($atalhos);
        $papelCurto = trim((string) ($dados['usuario']['tipo'] ?? 'Usuário')) ?: 'Usuário';
        $escopoCurto = trim((string) ($ctx['escopo'] ?? 'Rede municipal')) ?: 'Rede municipal';
        $anoLetivo = (int) ($ctx['ano'] ?? date('Y'));
        $atualizadoEm = $ctx['atualizado']
            ?? (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\DataHora::class)
                ? \iEducar\Packages\BuritiLagunaAmbiente\Support\DataHora::absoluto(now())
                : now()->format('d/m/Y H:i'));
        $caixaPorPaginaDia = 2;
    @endphp
    @if(session('seguranca.aviso_login') && $avisosSeguranca !== [])
        <div class="flash alerta hero-seguranca-flash" role="alert">
            <i class="fa fa-shield" aria-hidden="true"></i>
            <div>
                <strong>Atenção de segurança</strong>
                <span>Há pendências na sua conta (senha e/ou duplo fator). Veja o lembrete ao lado das boas-vindas.</span>
            </div>
        </div>
    @endif
    <section class="hero-painel-v2{{ $avisosSeguranca !== [] ? ' hero-painel-v2--com-lembrete' : '' }}" aria-label="Boas-vindas e caixa do dia">
        <div class="hero-copy hero-copy--{{ $periodo }}" data-periodo="{{ $periodo }}">
            <span class="hero-periodo" title="{{ $periodoRotulo }}" aria-label="Período: {{ $periodoRotulo }}">
                <i class="fa {{ $periodoIcone }}" aria-hidden="true"></i>
            </span>
            <p class="brand-mark">
                <span class="brand-mark__entidade">{{ $municipio }}</span>
                <span class="brand-mark__sep" aria-hidden="true"></span>
                <span class="brand-mark__ano">{{ $anoLetivo }}</span>
            </p>
            <h1 class="hero-papel">{{ $papelCurto }}</h1>
            <p class="lead">{{ $escopoCurto }}</p>
        </div>

        @if($avisosSeguranca !== [])
            <aside class="hero-lembrete-mfa" role="status" aria-label="Lembrete de segurança">
                <p class="hero-lembrete-mfa__kicker"><i class="fa fa-shield" aria-hidden="true"></i> Segurança</p>
                <h2 class="hero-lembrete-mfa__titulo">Lembrete</h2>
                <div class="hero-lembrete-mfa__lista">
                    @foreach($avisosSeguranca as $av)
                        <a class="hero-lembrete-mfa__item tom-{{ $av['tom'] ?? 'alerta' }}" href="{{ $av['url'] ?? url('/seguranca/minha') }}">
                            <i class="fa {{ $av['icone'] ?? 'fa-shield' }}" aria-hidden="true"></i>
                            <span>
                                <strong>{{ $av['titulo'] }}</strong>
                                @if(!empty($av['detalhe']))
                                    <small>{{ $av['detalhe'] }}</small>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
                <a class="hero-lembrete-mfa__cta" href="{{ url('/seguranca/minha') }}">Configurar MFA <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </aside>
        @endif

        <article class="prio-card hero-caixa" aria-label="Caixa do dia" id="caixa-dia-root" data-por-pagina="{{ $caixaPorPaginaDia }}">
            <header class="prio-head-row">
                <div>
                    <p class="sec-kicker">Hoje</p>
                    <h2>Caixa do dia <span class="sec-count" id="caixa-dia-count">{{ count($caixaItens) }}</span></h2>
                </div>
                <a class="prio-foot-link" href="{{ url('/notificacoes') }}">Ver todas <i class="fa fa-arrow-right"></i></a>
            </header>

            <div class="ls-filtros ls-filtros--caixa-dia" role="toolbar" aria-label="Filtros da caixa do dia">
                <div class="ls-filtros__grupo" aria-label="Tipo">
                    <span class="ls-filtros__legenda">Tipo</span>
                    <div class="ls-filtros__chips">
                        <button type="button" class="ls-chip is-ativo" data-caixa-filtro="" title="Todos">
                            <i class="fa fa-th-large" aria-hidden="true"></i> Todos
                        </button>
                        <button type="button" class="ls-chip" data-caixa-filtro="assinatura" title="Assinatura">
                            <i class="fa fa-pencil-square-o" aria-hidden="true"></i> Assinatura
                        </button>
                        <button type="button" class="ls-chip" data-caixa-filtro="notif" title="Notificações">
                            <i class="fa fa-bell" aria-hidden="true"></i> Avisos
                        </button>
                        <button type="button" class="ls-chip" data-caixa-filtro="escola" title="Escola">
                            <i class="fa fa-building" aria-hidden="true"></i> Escola
                        </button>
                    </div>
                </div>
                <div class="ls-filtros__grupo" aria-label="Prioridade">
                    <span class="ls-filtros__legenda">Prioridade</span>
                    <div class="ls-filtros__chips">
                        <button type="button" class="ls-chip is-ativo" data-caixa-tom="" title="Qualquer">Todas</button>
                        <button type="button" class="ls-chip ls-chip--critico" data-caixa-tom="erro" title="Crítico">
                            <i class="fa fa-exclamation-circle" aria-hidden="true"></i> Crítico
                        </button>
                        <button type="button" class="ls-chip" data-caixa-tom="alerta" title="Alerta">
                            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Alerta
                        </button>
                    </div>
                </div>
            </div>

            <ul class="lista lista-compacta" id="caixa-dia-lista" @if($caixaItens === []) hidden @endif></ul>
            <p class="vazio" id="caixa-dia-vazio" @if($caixaItens !== []) hidden @endif>Nada pendente.</p>
            <div class="ls-painel__rodape ls-painel__rodape--caixa-dia">
                <p class="ls-painel__mais" id="caixa-dia-resumo">Mostrando 0 de 0</p>
                <nav class="ls-paginacao" id="caixa-dia-paginacao" aria-label="Páginas da caixa do dia"></nav>
            </div>
            <script type="application/json" id="caixa-dia-catalogo">@json($caixaItens)</script>
        </article>
    </section>

    @php
        $lsPainelOk = !empty($dados['assinatura_digital_ok']);
        $filaAssinaturas = $lsPainelOk ? ($dados['assinaturas_pendentes'] ?? []) : [];
        $caixaAssinadasRaw = $lsPainelOk ? ($dados['assinaturas_assinadas'] ?? []) : [];
        if (isset($caixaAssinadasRaw['itens']) || isset($caixaAssinadasRaw['totais']) || isset($caixaAssinadasRaw['catalogo'])) {
            $caixaAssinadas = $caixaAssinadasRaw['itens'] ?? [];
            $caixaCatalogo = $caixaAssinadasRaw['catalogo'] ?? $caixaAssinadas;
            $caixaTotais = $caixaAssinadasRaw['totais'] ?? ['todos' => count($caixaCatalogo), 'andamento' => 0, 'prontos' => 0];
            $caixaModulos = $caixaAssinadasRaw['modulos'] ?? [];
            $caixaPorPagina = (int) ($caixaAssinadasRaw['por_pagina'] ?? 5);
        } else {
            $caixaAssinadas = is_array($caixaAssinadasRaw) ? $caixaAssinadasRaw : [];
            $caixaCatalogo = $caixaAssinadas;
            $caixaTotais = [
                'todos' => count($caixaAssinadas),
                'andamento' => collect($caixaAssinadas)->where('estado', 'parcial')->count(),
                'prontos' => collect($caixaAssinadas)->where('estado', 'completo')->count(),
            ];
            $caixaModulos = [];
            $caixaPorPagina = 5;
        }
        $qPendentes = count($filaAssinaturas);
        $qAssinados = (int) ($caixaTotais['todos'] ?? count($caixaCatalogo));
        $filaCritica = collect($filaAssinaturas)->contains(static fn ($i) => ! empty($i['critico_fila']) || ((int) ($i['dias_em_fila'] ?? 0) > 7));
        $filaModulosMap = [];
        foreach ($filaAssinaturas as $fp) {
            $chaveMod = (string) ($fp['modulo'] ?? 'outros');
            if ($chaveMod === '' || isset($filaModulosMap[$chaveMod])) {
                continue;
            }
            $filaModulosMap[$chaveMod] = [
                'chave' => $chaveMod,
                'rotulo' => (string) ($fp['modulo_rotulo'] ?? $chaveMod),
            ];
        }
        uasort($filaModulosMap, static fn (array $a, array $b): int => strcmp($a['rotulo'], $b['rotulo']));
        $filaModulos = array_values($filaModulosMap);
    @endphp
    @if($lsPainelOk)
        @php $lsPodeRecolher = $qPendentes === 0; @endphp
        <section class="ls-painel{{ $lsPodeRecolher ? ' ls-painel--recolhivel' : ' ls-painel--fixido' }}"
                 aria-label="LibreSign"
                 id="ls-painel-root"
                 data-por-pagina="{{ $caixaPorPagina }}"
                 data-total-geral="{{ $qAssinados }}"
                 data-recolhivel="{{ $lsPodeRecolher ? '1' : '0' }}"
                 data-pendentes="{{ $qPendentes }}">
            <header class="ls-painel__topo">
                <div class="ls-painel__marca">
                    <span class="ls-painel__ico" aria-hidden="true"><i class="fa fa-pencil-square-o"></i></span>
                    <p class="sec-kicker" style="margin:0">LibreSign</p>
                    @if($qPendentes > 0)
                        <span class="ls-painel__badge-pend{{ $filaCritica ? ' is-critico' : '' }}" title="Assinaturas pendentes{{ $filaCritica ? ' · há documento há mais de 7 dias' : '' }}">{{ $qPendentes }}</span>
                    @endif
                </div>
                <div class="ls-painel__links ls-painel__links--icos">
                    <a class="ls-painel__ico-link ls-painel__ico-link--fila{{ $filaCritica ? ' is-critico' : ($qPendentes > 0 ? ' is-alerta' : '') }}"
                       href="{{ url('/assinatura-digital/pendencias') }}"
                       title="Fila"
                       aria-label="Fila">
                        <i class="fa fa-hourglass-half" aria-hidden="true"></i>
                        <span class="ls-painel__ico-legenda">Fila</span>
                        @if($qPendentes > 0)
                            <span class="ls-painel__ico-count">{{ $qPendentes }}</span>
                        @endif
                    </a>
                    <a class="ls-painel__ico-link ls-painel__ico-link--assinados"
                       href="{{ url('/assinatura-digital/assinados') }}"
                       title="Assinados"
                       aria-label="Assinados">
                        <i class="fa fa-check-square-o" aria-hidden="true"></i>
                        <span class="ls-painel__ico-legenda">Assinados</span>
                    </a>
                    @if($lsPodeRecolher)
                        <button type="button"
                                class="ls-painel__ico-link ls-painel__toggle"
                                id="ls-painel-toggle"
                                aria-expanded="true"
                                aria-controls="ls-painel-corpo"
                                title="Recolher / expandir">
                            <i class="fa fa-chevron-up" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </header>

            <div class="ls-painel__corpo" id="ls-painel-corpo">
            @if(session('ok') && str_contains(mb_strtolower((string) session('ok')), 'assinatura'))
                <p class="ls-painel__flash ok">{{ session('ok') }}</p>
            @endif
            @if(session('erro') && str_contains(mb_strtolower((string) session('erro')), 'assin'))
                <p class="ls-painel__flash erro">{{ session('erro') }}</p>
            @endif

            <div class="ls-painel__grade">
                <div class="ls-painel__col ls-painel__col--pendentes">
                    <header class="ls-painel__col-head">
                        <h3><i class="fa fa-hourglass-half" aria-hidden="true"></i> Pendentes</h3>
                        <span class="sec-count" id="ls-fila-count">{{ $qPendentes }}</span>
                    </header>

                    @if($filaAssinaturas !== [])
                        <div class="ls-filtros ls-filtros--fila" role="toolbar" aria-label="Filtros da fila de pendentes">
                            <label class="ls-filtros__campo ls-filtros__campo--modulo">
                                <span>Módulo</span>
                                <select id="ls-fila-filtro-modulo" class="ls-filtros__select">
                                    <option value="">Todos</option>
                                    @foreach($filaModulos as $mod)
                                        <option value="{{ $mod['chave'] }}">{{ $mod['rotulo'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="ls-filtros__grupo ls-filtros__grupo--periodo" aria-label="Tempo em fila">
                                <span class="ls-filtros__legenda">Em fila</span>
                                <div class="ls-filtros__chips">
                                    <button type="button" class="ls-chip ls-chip--fila-tempo is-ativo" data-ls-fila-tempo="" title="Qualquer tempo">
                                        <i class="fa fa-th-large" aria-hidden="true"></i> Todas
                                    </button>
                                    <button type="button" class="ls-chip ls-chip--fila-tempo" data-ls-fila-tempo="ate7" title="Até 7 dias na fila">≤7d</button>
                                    <button type="button" class="ls-chip ls-chip--fila-tempo ls-chip--critico" data-ls-fila-tempo="mais7" title="Mais de 7 dias na fila">
                                        <i class="fa fa-clock-o" aria-hidden="true"></i> +7d
                                    </button>
                                    <button type="button" class="ls-chip ls-chip--fila-tempo ls-chip--critico" data-ls-fila-tempo="mais30" title="Mais de 30 dias na fila">
                                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> +30d
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <ul class="ls-painel__lista" id="ls-fila-lista" @if($filaAssinaturas === []) hidden @endif></ul>
                    <p class="ls-painel__vazio" id="ls-fila-vazio" @if($filaAssinaturas !== []) hidden @endif>
                        @if($filaAssinaturas === [])
                            Nada pendente da sua assinatura.
                        @else
                            Nenhum documento com esses filtros.
                        @endif
                    </p>
                    <script type="application/json" id="ls-fila-catalogo">@json($filaAssinaturas)</script>
                </div>

                <div class="ls-painel__col ls-painel__col--assinados">
                    <header class="ls-painel__col-head">
                        <h3><i class="fa fa-check-square-o" aria-hidden="true"></i> Já assinei</h3>
                        <span class="sec-count" id="ls-assinados-count">{{ $qAssinados }}</span>
                    </header>

                    <div class="ls-filtros" role="toolbar" aria-label="Filtros de documentos assinados">
                        <label class="ls-filtros__campo ls-filtros__campo--modulo">
                            <span>Módulo</span>
                            <select id="ls-filtro-modulo" class="ls-filtros__select">
                                <option value="">Todos</option>
                                @foreach($caixaModulos as $mod)
                                    <option value="{{ $mod['chave'] }}">{{ $mod['rotulo'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="ls-filtros__grupo ls-filtros__grupo--periodo" aria-label="Período">
                            <span class="ls-filtros__legenda">Período</span>
                            <div class="ls-filtros__chips">
                                <button type="button" class="ls-chip ls-chip--periodo is-ativo" data-ls-periodo="" title="Qualquer data">
                                    <i class="fa fa-th-large" aria-hidden="true"></i> Todas
                                </button>
                                <button type="button" class="ls-chip ls-chip--periodo" data-ls-periodo="7" title="Últimos 7 dias">7d</button>
                                <button type="button" class="ls-chip ls-chip--periodo" data-ls-periodo="30" title="Últimos 30 dias">30d</button>
                                <button type="button" class="ls-chip ls-chip--periodo" data-ls-periodo="mes" title="Este mês">
                                    <i class="fa fa-calendar" aria-hidden="true"></i> Mês
                                </button>
                            </div>
                        </div>
                        <div class="ls-filtros__grupo ls-filtros__grupo--escopo" aria-label="Tipo de assinatura">
                            <span class="ls-filtros__legenda">Assinatura</span>
                            <div class="ls-filtros__chips">
                                <button type="button" class="ls-chip ls-chip--escopo is-ativo" data-ls-escopo="" title="Todas as assinaturas">
                                    <i class="fa fa-th-large" aria-hidden="true"></i> Todas
                                </button>
                                <button type="button" class="ls-chip ls-chip--escopo" data-ls-escopo="unica" title="Assinatura única">
                                    <i class="fa fa-pencil" aria-hidden="true"></i> Única
                                </button>
                                <button type="button" class="ls-chip ls-chip--escopo" data-ls-escopo="multi" title="Assinatura em fila (várias etapas)">
                                    <i class="fa fa-users" aria-hidden="true"></i> Em fila
                                </button>
                            </div>
                        </div>
                    </div>

                    <ul class="ls-painel__lista" id="ls-assinados-lista"></ul>
                    <p class="ls-painel__vazio" id="ls-assinados-vazio" hidden>Nenhum documento com esses filtros.</p>

                    <div class="ls-painel__rodape" id="ls-assinados-rodape">
                        <p class="ls-painel__mais" id="ls-assinados-resumo">Mostrando 0 de 0</p>
                        <nav class="ls-paginacao" id="ls-assinados-paginacao" aria-label="Páginas de assinados"></nav>
                    </div>
                </div>
            </div>

            <script type="application/json" id="ls-assinados-catalogo">@json($caixaCatalogo)</script>
            </div>{{-- /.ls-painel__corpo --}}
        </section>
        @include('assinatura::partials.modal-pdf')
    @endif

    <section class="modulos-plano" aria-label="SGE — Ecossistema i-Educar">
        <header class="sec-head sec-head-tight">
            <div>
                <p class="sec-kicker">Ecossistema</p>
                <h2>SGE — Ecossistema i-Educar <span class="sec-count">{{ $qModulos }}</span></h2>
            </div>
            <p class="sec-lead">Módulos liberados ao seu perfil. Desativados pela SME ficam visíveis, sem acesso.</p>
        </header>

        @if(session('erro'))
            <div class="bloco" style="border-top-color:var(--erro,#9b2c2c)">
                <p class="vazio" style="color:var(--erro,#9b2c2c);margin:0">{{ session('erro') }}</p>
            </div>
        @endif

        @if($atalhos === [])
            <div class="bloco">
                <p class="vazio">Nenhum módulo liberado para o seu perfil. Use o i-Educar no cabeçalho para a tela padrão.</p>
            </div>
        @else
            <div class="mod-cards">
                @foreach($atalhos as $atalho)
                    @php
                        $contadores = $atalho['contadores'] ?? [];
                        $ativo = !array_key_exists('modulo_ativo', $atalho) || !empty($atalho['modulo_ativo']);
                    @endphp
                    <article class="mod-card{{ $contadores !== [] && $ativo ? ' tem-badge' : '' }}{{ $ativo ? '' : ' mod-card--off' }}"
                             style="--mod: {{ $ativo ? ($atalho['cor'] ?? 'var(--destaque)') : '#98a2b3' }}"
                             @if(!$ativo) title="Módulo desativado nas configurações municipais" aria-disabled="true" @endif>
                        @if($ativo)
                            <a class="mod-card-link"
                               href="{{ $atalho['url'] }}"
                               @if(!empty($atalho['externo'])) target="_blank" rel="noopener" @endif>
                                <span class="mod-ico"><i class="fa {{ $atalho['icone'] }}"></i></span>
                                <span class="mod-body">
                                    <strong>{{ $atalho['titulo'] }}@if(!empty($atalho['externo'])) <i class="fa fa-external-link" aria-hidden="true"></i>@endif</strong>
                                    <small>{{ $atalho['descricao'] ?? '' }}</small>
                                </span>
                            </a>
                            @if($contadores !== [])
                                <div class="mod-badges" aria-label="Indicadores do módulo">
                                    @foreach($contadores as $c)
                                        @php
                                            $href = $c['url'] ?? $atalho['url'];
                                            $valorNum = is_numeric($c['valor']) ? (float) $c['valor'] : null;
                                            $valor = $valorNum !== null
                                                ? ($valorNum > 99 ? '99+' : number_format($valorNum, 0, ',', '.'))
                                                : $c['valor'];
                                            $tip = trim(($c['rotulo'] ?? '').(!empty($c['detalhe']) ? ' — '.$c['detalhe'] : ''));
                                            $destaque = !empty($c['destaque']) || in_array($c['tom'] ?? '', ['alerta', 'erro'], true);
                                        @endphp
                                        <a class="mod-badge {{ $c['tom'] ?? 'info' }}{{ $destaque ? ' pulso' : '' }}"
                                           href="{{ $href }}"
                                           title="{{ $tip !== '' ? $tip : ($c['rotulo'] ?? 'Indicador') }}"
                                           aria-label="{{ $tip !== '' ? $tip : ($c['rotulo'] ?? 'Indicador') }}"
                                           @if(str_starts_with((string) $href, 'http') && !str_starts_with((string) $href, url('/'))) target="_blank" rel="noopener" @endif>
                                            {{ $valor }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="mod-card-link mod-card-link--off" role="presentation">
                                <span class="mod-ico"><i class="fa {{ $atalho['icone'] }}"></i></span>
                                <span class="mod-body">
                                    <strong>{{ $atalho['titulo'] }} <span class="mod-off-tag">Desligado</span></strong>
                                    <small>Desativado pela SME — sem acesso por enquanto.</small>
                                </span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if($grupos !== [] || $kpisFlat !== [])
        <section class="bloco bloco-kpis bloco-kpis-v2" style="border-top-color:var(--acento)">
            <header class="sec-head sec-head-tight">
                <div>
                    <p class="sec-kicker">Indicadores</p>
                    <h2>Rede no seu escopo</h2>
                </div>
                <p class="sec-lead">Conforme as permissões do seu perfil.</p>
            </header>

            @if($grupos !== [])
                <div class="kpi-grupos-grid">
                    @foreach($grupos as $grupo)
                        <div class="kpi-grupo">
                            <div class="kpi-grupo-cab">
                                <i class="fa {{ $grupo['icone'] }}"></i>
                                {{ $grupo['nome'] }}
                            </div>
                            <div class="kpis">
                                @foreach($grupo['itens'] as $kpi)
                                    @include('dashboard.partials.kpi-card', ['kpi' => $kpi])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="kpis">
                    @foreach($kpisFlat as $kpi)
                        @include('dashboard.partials.kpi-card', ['kpi' => $kpi])
                    @endforeach
                </div>
            @endif
        </section>
    @endif

</div>

<aside class="rodape-operacao" aria-label="Operação e acesso">
    <div class="rodape-operacao-inner">
        <span class="rodape-operacao-kicker"><i class="fa fa-sliders" aria-hidden="true"></i> Operação</span>
        <ul class="rodape-operacao-lista">
            @php
                $verModulosCi = auth()->check()
                    && method_exists(auth()->user(), 'isAdmin')
                    && auth()->user()->isAdmin()
                    && \Illuminate\Support\Facades\View::exists('laguna-ambiente::partials.rodape-modulos-deploy-botao')
                    && \Illuminate\Support\Facades\View::exists('laguna-ambiente::partials.rodape-modulos-deploy-painel');
            @endphp
            @if($verModulosCi)
                @include('laguna-ambiente::partials.rodape-modulos-deploy-botao')
            @endif
            @foreach($rodapeOperacao as $op)
                @php
                    $ehNotif = str_contains(mb_strtolower((string) ($op['rotulo'] ?? '')), 'notifica');
                @endphp
                <li class="rodape-op {{ $op['tom'] ?? 'info' }}"
                    @if($ehNotif) data-rodape-notif-item @endif>
                    @if(!empty($op['url']))
                        <a href="{{ $op['url'] }}"
                           title="{{ $op['detalhe'] ?? $op['rotulo'] }}"
                           @if(str_starts_with((string) $op['url'], 'http') && !str_starts_with((string) $op['url'], url('/'))) target="_blank" rel="noopener" @endif>
                            <i class="fa {{ $op['icone'] ?? 'fa-info-circle' }}" aria-hidden="true"></i>
                            <span class="rodape-op-rotulo">{{ $op['rotulo'] }}</span>
                            <strong @if($ehNotif) data-rodape-notif-valor @endif>{{ $op['valor'] }}</strong>
                        </a>
                    @else
                        <span title="{{ $op['detalhe'] ?? $op['rotulo'] }}">
                            <i class="fa {{ $op['icone'] ?? 'fa-info-circle' }}" aria-hidden="true"></i>
                            <span class="rodape-op-rotulo">{{ $op['rotulo'] }}</span>
                            <strong @if($ehNotif) data-rodape-notif-valor @endif>{{ $op['valor'] }}</strong>
                        </span>
                    @endif
                </li>
            @endforeach
            <li class="rodape-op info rodape-op-atualizado">
                <span title="Momento em que os indicadores deste painel foram montados">
                    <i class="fa fa-refresh" aria-hidden="true"></i>
                    <span class="rodape-op-rotulo">Atualizado</span>
                    <strong data-rodape-atualizado>{{ $atualizadoEm }}</strong>
                </span>
            </li>
        </ul>
        <a class="rodape-org-github"
           href="https://github.com/i-Educar"
           target="_blank"
           rel="noopener noreferrer"
           title="Organização i-Educar no GitHub"
           aria-label="Abrir organização i-Educar no GitHub">
            <img
                src="{{ asset('vendor/buriti/ieducar-org.png') }}"
                alt=""
                width="22"
                height="22"
                decoding="async"
                onerror="this.onerror=null;this.src='https://avatars.githubusercontent.com/u/317414325?s=64&amp;v=4'">
            <span class="rodape-org-github-mark" aria-hidden="true">
                <svg viewBox="0 0 16 16" width="11" height="11" fill="currentColor" focusable="false">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
                </svg>
            </span>
        </a>
    </div>
</aside>
@if(!empty($verModulosCi))
    @include('laguna-ambiente::partials.rodape-modulos-deploy-painel', [
        'urlJson' => url('/laguna-ambiente/modulos-deploy'),
    ])
@endif
@endsection

@push('scripts')
<script>
(function () {
  /* ---- Caixa do dia: filtros + paginação ---- */
  var caixaRoot = document.getElementById('caixa-dia-root');
  if (caixaRoot) {
    var rawCaixa = document.getElementById('caixa-dia-catalogo');
    var catalogoCaixa = [];
    try { catalogoCaixa = JSON.parse(rawCaixa ? rawCaixa.textContent : '[]'); } catch (e) { catalogoCaixa = []; }
    if (!Array.isArray(catalogoCaixa)) catalogoCaixa = [];
    var estadoCaixa = { filtro: '', tom: '', pagina: 1 };
    var porPaginaCaixa = Math.max(2, parseInt(caixaRoot.getAttribute('data-por-pagina') || '2', 10) || 2);
    var elCaixaLista = document.getElementById('caixa-dia-lista');
    var elCaixaVazio = document.getElementById('caixa-dia-vazio');
    var elCaixaCount = document.getElementById('caixa-dia-count');
    var elCaixaResumo = document.getElementById('caixa-dia-resumo');
    var elCaixaPag = document.getElementById('caixa-dia-paginacao');

    function escapeCaixa(s) {
      return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function filtrarCaixa() {
      return catalogoCaixa.filter(function (item) {
        if (estadoCaixa.filtro && String(item.filtro || item.tipo || '') !== estadoCaixa.filtro) return false;
        if (estadoCaixa.tom && String(item.tom || '') !== estadoCaixa.tom) return false;
        return true;
      });
    }

    function renderCaixaItem(item) {
      var url = item.url || '';
      var cta = item.cta || 'Abrir';
      return (
        '<li class="atencao ' + escapeCaixa(item.tom || 'info') + '">' +
          '<span class="ico"><i class="fa ' + escapeCaixa(item.icone || 'fa-exclamation-circle') + '"></i></span>' +
          '<div>' +
            '<strong>' + escapeCaixa(item.titulo || '') + '</strong>' +
            (item.detalhe ? ('<small>' + escapeCaixa(item.detalhe) + '</small>') : '') +
          '</div>' +
          (url ? ('<a class="botao claro" href="' + escapeCaixa(url) + '">' + escapeCaixa(cta) + '</a>') : '') +
        '</li>'
      );
    }

    function renderCaixa() {
      var filtrados = filtrarCaixa();
      var total = filtrados.length;
      var ultima = Math.max(1, Math.ceil(total / porPaginaCaixa));
      if (estadoCaixa.pagina > ultima) estadoCaixa.pagina = ultima;
      var ini = (estadoCaixa.pagina - 1) * porPaginaCaixa;
      var paginaItens = filtrados.slice(ini, ini + porPaginaCaixa);

      if (elCaixaCount) elCaixaCount.textContent = String(total);
      if (elCaixaLista) {
        elCaixaLista.innerHTML = paginaItens.map(renderCaixaItem).join('');
        elCaixaLista.hidden = total === 0;
      }
      if (elCaixaVazio) {
        elCaixaVazio.hidden = total > 0;
        elCaixaVazio.textContent = catalogoCaixa.length === 0
          ? 'Nada pendente.'
          : 'Nenhum item com esses filtros.';
      }

      var mostrando = paginaItens.length;
      var de = total === 0 ? 0 : ini + 1;
      var ate = ini + mostrando;
      if (elCaixaResumo) {
        elCaixaResumo.textContent = total === catalogoCaixa.length
          ? ('Mostrando ' + (total ? (de + '–' + ate) : '0') + ' de ' + catalogoCaixa.length)
          : ('Mostrando ' + (total ? (de + '–' + ate) : '0') + ' de ' + total + ' filtrados · ' + catalogoCaixa.length + ' no total');
      }

      if (elCaixaPag) {
        elCaixaPag.innerHTML = '';
        if (ultima > 1) {
          var prev = document.createElement('button');
          prev.type = 'button';
          prev.textContent = '‹';
          prev.disabled = estadoCaixa.pagina <= 1;
          prev.setAttribute('aria-label', 'Página anterior');
          prev.addEventListener('click', function () { estadoCaixa.pagina -= 1; renderCaixa(); });
          elCaixaPag.appendChild(prev);

          for (var i = 1; i <= ultima; i++) {
            (function (num) {
              var b = document.createElement('button');
              b.type = 'button';
              b.textContent = String(num);
              if (num === estadoCaixa.pagina) b.classList.add('is-ativo');
              b.addEventListener('click', function () { estadoCaixa.pagina = num; renderCaixa(); });
              elCaixaPag.appendChild(b);
            })(i);
          }

          var next = document.createElement('button');
          next.type = 'button';
          next.textContent = '›';
          next.disabled = estadoCaixa.pagina >= ultima;
          next.setAttribute('aria-label', 'Próxima página');
          next.addEventListener('click', function () { estadoCaixa.pagina += 1; renderCaixa(); });
          elCaixaPag.appendChild(next);
        }
      }
    }

    caixaRoot.querySelectorAll('[data-caixa-filtro]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        caixaRoot.querySelectorAll('[data-caixa-filtro]').forEach(function (b) { b.classList.remove('is-ativo'); });
        btn.classList.add('is-ativo');
        estadoCaixa.filtro = btn.getAttribute('data-caixa-filtro') || '';
        estadoCaixa.pagina = 1;
        renderCaixa();
      });
    });
    caixaRoot.querySelectorAll('[data-caixa-tom]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        caixaRoot.querySelectorAll('[data-caixa-tom]').forEach(function (b) { b.classList.remove('is-ativo'); });
        btn.classList.add('is-ativo');
        estadoCaixa.tom = btn.getAttribute('data-caixa-tom') || '';
        estadoCaixa.pagina = 1;
        renderCaixa();
      });
    });
    renderCaixa();
  }
})();

(function () {
  var root = document.getElementById('ls-painel-root');
  if (!root) return;

  // Recolher só quando NÃO há pendências.
  var podeRecolher = root.getAttribute('data-recolhivel') === '1';
  var toggle = document.getElementById('ls-painel-toggle');
  var corpo = document.getElementById('ls-painel-corpo');
  var storageKey = 'ls-painel-recolhido';
  if (podeRecolher && toggle && corpo) {
    var aplicar = function (recolhido) {
      root.classList.toggle('is-recolhido', recolhido);
      toggle.setAttribute('aria-expanded', recolhido ? 'false' : 'true');
      toggle.setAttribute('title', recolhido ? 'Expandir' : 'Recolher');
      try { localStorage.setItem(storageKey, recolhido ? '1' : '0'); } catch (e) {}
    };
    var salvo = false;
    try { salvo = localStorage.getItem(storageKey) === '1'; } catch (e) {}
    aplicar(salvo);
    toggle.addEventListener('click', function () {
      aplicar(!root.classList.contains('is-recolhido'));
    });
  } else {
    root.classList.remove('is-recolhido');
    try { localStorage.removeItem(storageKey); } catch (e) {}
  }

  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  /* ---- Fila (pendentes): filtros módulo + tempo em fila ---- */
  var rawFila = document.getElementById('ls-fila-catalogo');
  var catalogoFila = [];
  try { catalogoFila = JSON.parse(rawFila ? rawFila.textContent : '[]'); } catch (e) { catalogoFila = []; }
  if (!Array.isArray(catalogoFila)) catalogoFila = [];
  var estadoFila = { modulo: '', tempo: '' };
  var elFilaLista = document.getElementById('ls-fila-lista');
  var elFilaVazio = document.getElementById('ls-fila-vazio');
  var elFilaCount = document.getElementById('ls-fila-count');
  var elFilaModulo = document.getElementById('ls-fila-filtro-modulo');

  function diasEmFila(item) {
    if (typeof item.dias_em_fila === 'number' && !isNaN(item.dias_em_fila)) return item.dias_em_fila;
    var iso = item.criado_em_iso || item.criado_em || '';
    if (!iso) return null;
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
      var m = String(iso).match(/(\d{2})\/(\d{2})\/(\d{4})/);
      if (!m) return null;
      d = new Date(+m[3], +m[2] - 1, +m[1]);
    }
    return Math.max(0, Math.floor((Date.now() - d.getTime()) / 86400000));
  }

  function filtrarFila() {
    return catalogoFila.filter(function (item) {
      if (estadoFila.modulo && String(item.modulo || '') !== estadoFila.modulo) return false;
      var dias = diasEmFila(item);
      if (estadoFila.tempo === 'ate7') {
        if (dias === null || dias > 7) return false;
      } else if (estadoFila.tempo === 'mais7') {
        if (dias === null || dias <= 7) return false;
      } else if (estadoFila.tempo === 'mais30') {
        if (dias === null || dias <= 30) return false;
      }
      return true;
    });
  }

  function renderFilaItem(item) {
    var dias = diasEmFila(item);
    var critico = !!item.critico_fila || (dias !== null && dias > 7);
    var cargo = item.cargo || item.nome || 'Aguardando sua assinatura';
    var small = escapeHtml(cargo);
    if (item.modulo_rotulo) small += ' · ' + escapeHtml(item.modulo_rotulo);
    if (dias !== null) {
      small += ' · ' + (dias === 0 ? 'hoje na fila' : (dias + ' dia' + (dias === 1 ? '' : 's') + ' na fila'));
    }
    var pdfUrl = item.url_pdf || '';
    var verUrl = item.url_ver || '';
    var assinarUrl = item.url_assinar || '';
    return (
      '<li>' +
        '<div class="ls-painel__item-main">' +
          '<span class="ls-painel__item-ico ' + (critico ? 'critico' : 'alerta') + '" aria-hidden="true">' +
            '<i class="fa ' + (critico ? 'fa-exclamation-triangle' : 'fa-file-text-o') + '"></i>' +
          '</span>' +
          '<div>' +
            '<strong>' + escapeHtml(item.titulo || 'Documento') + '</strong>' +
            '<small>' + small + '</small>' +
          '</div>' +
        '</div>' +
        '<div class="assina-acoes">' +
          '<button type="button" class="botao claro js-abrir-pdf-modal"' +
            ' data-pdf-url="' + escapeHtml(pdfUrl) + '"' +
            ' data-ver-url="' + escapeHtml(verUrl) + '"' +
            ' data-titulo="' + escapeHtml(item.titulo || '') + '"' +
            ' data-cargo="' + escapeHtml(cargo) + '"' +
            ' data-assinar-url="' + escapeHtml(assinarUrl) + '"' +
            ' data-redirect="/painel"' +
            ' title="Ver PDF">' +
            '<i class="fa fa-eye" aria-hidden="true"></i> Ver' +
          '</button>' +
          (assinarUrl
            ? ('<form method="post" action="' + escapeHtml(assinarUrl) + '">' +
                '<input type="hidden" name="_token" value="' + escapeHtml(CSRF) + '">' +
                '<input type="hidden" name="_redirect" value="/painel">' +
                '<button type="submit" class="botao" title="Assinar digitalmente" onclick="return confirm(\'Confirmar sua assinatura digital neste documento?\')">' +
                  '<i class="fa fa-pencil" aria-hidden="true"></i> Assinar' +
                '</button>' +
              '</form>')
            : '') +
        '</div>' +
      '</li>'
    );
  }

  function renderFila() {
    if (!elFilaLista) return;
    var filtrados = filtrarFila();
    var total = filtrados.length;
    if (elFilaCount) elFilaCount.textContent = String(total);
    elFilaLista.innerHTML = filtrados.map(renderFilaItem).join('');
    elFilaLista.hidden = total === 0;
    if (elFilaVazio) {
      elFilaVazio.hidden = total > 0;
      if (catalogoFila.length === 0) {
        elFilaVazio.textContent = 'Nada pendente da sua assinatura.';
      } else if (total === 0) {
        elFilaVazio.textContent = 'Nenhum documento com esses filtros.';
      }
    }
  }

  if (elFilaModulo) {
    elFilaModulo.addEventListener('change', function () {
      estadoFila.modulo = elFilaModulo.value || '';
      renderFila();
    });
  }
  root.querySelectorAll('[data-ls-fila-tempo]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      root.querySelectorAll('[data-ls-fila-tempo]').forEach(function (b) { b.classList.remove('is-ativo'); });
      btn.classList.add('is-ativo');
      estadoFila.tempo = btn.getAttribute('data-ls-fila-tempo') || '';
      renderFila();
    });
  });

  var raw = document.getElementById('ls-assinados-catalogo');
  var catalogo = [];
  try { catalogo = JSON.parse(raw ? raw.textContent : '[]'); } catch (e) { catalogo = []; }
  if (!Array.isArray(catalogo)) catalogo = [];

  var porPagina = Math.max(3, parseInt(root.getAttribute('data-por-pagina') || '5', 10) || 5);
  var totalGeral = parseInt(root.getAttribute('data-total-geral') || String(catalogo.length), 10) || catalogo.length;
  var estado = { modulo: '', periodo: '', escopo: '', pagina: 1 };

  var elLista = document.getElementById('ls-assinados-lista');
  var elVazio = document.getElementById('ls-assinados-vazio');
  var elResumo = document.getElementById('ls-assinados-resumo');
  var elPag = document.getElementById('ls-assinados-paginacao');
  var elCount = document.getElementById('ls-assinados-count');
  var elModulo = document.getElementById('ls-filtro-modulo');

  function parseData(item) {
    if (item.assinei_em_iso) {
      var d = new Date(item.assinei_em_iso);
      if (!isNaN(d.getTime())) return d;
    }
    var txt = item.assinei_em || item.quando || '';
    // d/m/Y H:i
    var m = String(txt).match(/(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?/);
    if (m) {
      return new Date(+m[3], +m[2] - 1, +m[1], +(m[4] || 0), +(m[5] || 0));
    }
    return null;
  }

  function filtrar() {
    var agora = new Date();
    var inicioMes = new Date(agora.getFullYear(), agora.getMonth(), 1);
    return catalogo.filter(function (item) {
      if (estado.modulo && String(item.modulo || '') !== estado.modulo) return false;

      var total = parseInt(item.assinaturas_total || 0, 10) || 0;
      var unica = item.assinatura_unica === true || total <= 1;
      if (estado.escopo === 'unica' && !unica) return false;
      if (estado.escopo === 'multi' && unica) return false;

      if (estado.periodo) {
        var dt = parseData(item);
        if (!dt) return false;
        if (estado.periodo === 'mes') {
          if (dt < inicioMes) return false;
        } else {
          var dias = parseInt(estado.periodo, 10);
          var corte = new Date(agora.getTime() - dias * 86400000);
          if (dt < corte) return false;
        }
      }
      return true;
    });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  renderFila();

  function renderItem(item) {
    var ok = (item.estado || '') === 'completo';
    var sub = item.resumo_tempo || (item.cargo ? ('Seu papel: ' + item.cargo) : '');
    var small = escapeHtml(item.estado_rotulo || '');
    if (item.progresso) small += ' · ' + escapeHtml(item.progresso);
    if (!ok && item.pendentes_outros && item.pendentes_outros.length) {
      small += ' · Falta: ' + escapeHtml(item.pendentes_outros.join(', '));
    } else if (item.assinei_em_relativo) {
      small += ' · ' + escapeHtml(item.assinei_em_relativo);
    } else if (item.assinei_em) {
      small += ' · ' + escapeHtml(item.assinei_em);
    }
    if (item.modulo_rotulo) small += ' · ' + escapeHtml(item.modulo_rotulo);

    return (
      '<li>' +
        '<div class="ls-painel__item-main">' +
          '<span class="ls-painel__item-ico ' + (ok ? 'ok' : 'alerta') + '" aria-hidden="true">' +
            '<i class="fa ' + (ok ? 'fa-check-circle' : 'fa-hourglass-half') + '"></i>' +
          '</span>' +
          '<div>' +
            '<strong>' + escapeHtml(item.titulo || 'Documento') + '</strong>' +
            '<small>' + small + '</small>' +
          '</div>' +
        '</div>' +
        '<div class="assina-acoes">' +
          '<button type="button" class="botao claro js-abrir-pdf-modal"' +
            ' data-pdf-url="' + escapeHtml(item.url_pdf || '') + '"' +
            ' data-ver-url="' + escapeHtml(item.url_ver || '') + '"' +
            ' data-titulo="' + escapeHtml(item.titulo || '') + '"' +
            ' data-cargo="' + escapeHtml(sub) + '"' +
            ' title="Ver PDF">' +
            '<i class="fa fa-eye" aria-hidden="true"></i> Ver' +
          '</button>' +
        '</div>' +
      '</li>'
    );
  }

  function render() {
    var filtrados = filtrar();
    var totalFiltro = filtrados.length;
    var ultima = Math.max(1, Math.ceil(totalFiltro / porPagina));
    if (estado.pagina > ultima) estado.pagina = ultima;
    var ini = (estado.pagina - 1) * porPagina;
    var paginaItens = filtrados.slice(ini, ini + porPagina);

    if (elCount) elCount.textContent = String(totalFiltro);
    if (elLista) elLista.innerHTML = paginaItens.map(renderItem).join('');
    if (elVazio) elVazio.hidden = totalFiltro > 0;
    if (elLista) elLista.hidden = totalFiltro === 0;

    var mostrando = paginaItens.length;
    var de = totalFiltro === 0 ? 0 : ini + 1;
    var ate = ini + mostrando;
    var texto = totalFiltro === totalGeral
      ? ('Mostrando ' + (totalFiltro ? (de + '–' + ate) : '0') + ' de ' + totalGeral)
      : ('Mostrando ' + (totalFiltro ? (de + '–' + ate) : '0') + ' de ' + totalFiltro + ' filtrados · ' + totalGeral + ' no total');
    if (elResumo) elResumo.textContent = texto;

    if (elPag) {
      elPag.innerHTML = '';
      if (ultima > 1) {
        var prev = document.createElement('button');
        prev.type = 'button';
        prev.textContent = '‹';
        prev.disabled = estado.pagina <= 1;
        prev.setAttribute('aria-label', 'Página anterior');
        prev.addEventListener('click', function () { estado.pagina -= 1; render(); });
        elPag.appendChild(prev);

        var pages = [];
        if (ultima <= 7) {
          for (var i = 1; i <= ultima; i++) pages.push(i);
        } else {
          pages = [1];
          var start = Math.max(2, estado.pagina - 1);
          var end = Math.min(ultima - 1, estado.pagina + 1);
          if (start > 2) pages.push('…');
          for (var j = start; j <= end; j++) pages.push(j);
          if (end < ultima - 1) pages.push('…');
          pages.push(ultima);
        }
        pages.forEach(function (num) {
          if (num === '…') {
            var dots = document.createElement('span');
            dots.textContent = '…';
            dots.style.padding = '0 4px';
            dots.style.color = 'var(--suave,#5b6b76)';
            elPag.appendChild(dots);
            return;
          }
          var b = document.createElement('button');
          b.type = 'button';
          b.textContent = String(num);
          if (num === estado.pagina) b.classList.add('is-ativo');
          b.addEventListener('click', function () { estado.pagina = num; render(); });
          elPag.appendChild(b);
        });

        var next = document.createElement('button');
        next.type = 'button';
        next.textContent = '›';
        next.disabled = estado.pagina >= ultima;
        next.setAttribute('aria-label', 'Próxima página');
        next.addEventListener('click', function () { estado.pagina += 1; render(); });
        elPag.appendChild(next);
      }
    }
  }

  if (elModulo) {
    elModulo.addEventListener('change', function () {
      estado.modulo = elModulo.value || '';
      estado.pagina = 1;
      render();
    });
  }

  root.querySelectorAll('[data-ls-periodo]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      root.querySelectorAll('[data-ls-periodo]').forEach(function (b) { b.classList.remove('is-ativo'); });
      btn.classList.add('is-ativo');
      estado.periodo = btn.getAttribute('data-ls-periodo') || '';
      estado.pagina = 1;
      render();
    });
  });

  root.querySelectorAll('[data-ls-escopo]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      root.querySelectorAll('[data-ls-escopo]').forEach(function (b) { b.classList.remove('is-ativo'); });
      btn.classList.add('is-ativo');
      estado.escopo = btn.getAttribute('data-ls-escopo') || '';
      estado.pagina = 1;
      render();
    });
  });

  render();
})();
</script>
@endpush

<?php

namespace App\Services;

use App\Menu;
use App\Models\Announcement;
use App\Models\LegacyUserType;
use App\Models\Notification;
use App\Support\IdiarioPortal;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UserDashboardService
{
    public function __construct(
        private readonly MenuCacheService $menus,
    ) {}

    /**
     * @return array{
     *   usuario: array{nome:string,tipo:?string,login:?string,iniciais:string,foto:?string},
     *   contexto: array{ano:int,escopo:string,atualizado:string,saudacao:string},
     *   notificacoes: list<array{id:int,texto:string,link:?string,lida:bool,quando:?string}>,
     *   nao_lidas: int,
     *   atencoes: list<array{titulo:string,detalhe:string,tom:string,url:?string,icone:string}>,
     *   atalhos: list<array{titulo:string,descricao:?string,url:string,icone:string,externo:bool}>,
     *   kpis: list<array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string,grupo:string}>,
     *   kpi_grupos: list<array{id:string,nome:string,icone:string,itens:list<array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string,grupo:string}>}>,
     *   rodape_operacao: list<array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string,grupo:string}>
     * }
     */
    public function montar(User $user): array
    {
        $notificacoes = $this->notificacoes($user);
        $naoLidas = Notification::query()
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->count();

        $nome = (string) ($user->name ?? $user->login ?? 'Usuário');
        $atalhos = $this->atalhos($user);
        $escolas = $this->escolasEscopo($user);
        $assinaturaDigitalOk = $this->assinaturaDigitalDisponivel();
        $assinaturasPendentes = $assinaturaDigitalOk ? $this->assinaturasPendentes($user) : [];
        $assinaturasAssinadas = $assinaturaDigitalOk ? $this->assinaturasAssinadas($user) : [];
        $kpis = $this->indicadores($user, $naoLidas, $atalhos, $escolas);
        $atalhos = $this->anexarContadoresAosModulos($atalhos, $kpis);
        $hora = (int) date('G');
        // Identidade visual do card (sem texto de saudação na UI).
        $periodo = match (true) {
            $hora >= 0 && $hora < 5 => 'madrugada',
            $hora < 12 => 'manha',
            $hora < 18 => 'tarde',
            default => 'noite',
        };
        $saudacao = match ($periodo) {
            'madrugada' => 'Boa madrugada',
            'manha' => 'Bom dia',
            'tarde' => 'Boa tarde',
            default => 'Boa noite',
        };
        $seguranca = $this->statusSegurancaConta($user);

        return [
            'usuario' => [
                'nome' => $nome,
                'tipo' => $user->type->nm_tipo ?? null,
                'login' => $user->login ?? null,
                'iniciais' => $this->iniciais($nome),
                'foto' => session('logged_user_picture') ?: null,
            ],
            'contexto' => [
                'ano' => (int) date('Y'),
                'escopo' => $escolas === null ? 'Rede municipal' : (count($escolas).' escola(s) do seu perfil'),
                'atualizado' => class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\DataHora::class)
                    ? \iEducar\Packages\BuritiLagunaAmbiente\Support\DataHora::formatar(now())
                    : now()->format('d/m/Y H:i'),
                'saudacao' => $saudacao,
                'periodo' => $periodo,
            ],
            'seguranca' => $seguranca,
            'notificacoes' => $notificacoes,
            'nao_lidas' => $naoLidas,
            'atencoes' => $this->atencoes($user, $naoLidas, $escolas, count($assinaturasPendentes), $seguranca),
            'assinatura_digital_ok' => $assinaturaDigitalOk,
            'assinaturas_pendentes' => $assinaturasPendentes,
            'assinaturas_assinadas' => $assinaturasAssinadas,
            'atalhos' => $atalhos,
            'modulos_grupos' => $this->agruparModulosJornada($atalhos),
            'kpis' => $kpis,
            'kpi_grupos' => $this->agruparKpis($kpis, ['rede', 'qualidade']),
            'rodape_operacao' => array_values(array_filter(
                $kpis,
                static fn (array $k): bool => ($k['grupo'] ?? '') === 'operacao'
            )),
        ];
    }

    /**
     * @return array{
     *   disponivel:bool,
     *   senha_fora_politica:bool,
     *   mfa_ausente:bool,
     *   avisos:list<array{chave:string,titulo:string,detalhe:string,url:string,icone:string,tom:string}>
     * }
     */
    private function statusSegurancaConta(User $user): array
    {
        $vazio = [
            'disponivel' => false,
            'senha_fora_politica' => false,
            'mfa_ausente' => false,
            'avisos' => [],
        ];

        if (class_exists(\iEducar\Packages\BuritiSeguranca\Services\AvisoSegurancaContaService::class)) {
            try {
                return app(\iEducar\Packages\BuritiSeguranca\Services\AvisoSegurancaContaService::class)
                    ->avaliar($user);
            } catch (\Throwable) {
                // fallback abaixo
            }
        }

        try {
            if ($user->employee?->force_reset_password) {
                return [
                    'disponivel' => true,
                    'senha_fora_politica' => true,
                    'mfa_ausente' => false,
                    'avisos' => [[
                        'chave' => 'senha',
                        'titulo' => 'Troca de senha obrigatória',
                        'detalhe' => 'Sua senha precisa ser atualizada antes de continuar.',
                        'url' => route('change-password'),
                        'icone' => 'fa-lock',
                        'tom' => 'erro',
                    ]],
                ];
            }
        } catch (\Throwable) {
            //
        }

        return $vazio;
    }

    /**
     * Módulo Assinatura digital ativo (Laguna) e LibreSign configurado/respondendo.
     */
    private function assinaturaDigitalDisponivel(): bool
    {
        if (! class_exists(\iEducar\Packages\BuritiAssinaturaDigital\Services\PendenciasAssinaturaService::class)) {
            return false;
        }

        try {
            return app(\iEducar\Packages\BuritiAssinaturaDigital\Services\PendenciasAssinaturaService::class)
                ->painelDisponivel();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Documentos aguardando a assinatura digital do usuário logado.
     *
     * @return list<array<string,mixed>>
     */
    private function assinaturasPendentes(User $user): array
    {
        if (! class_exists(\iEducar\Packages\BuritiAssinaturaDigital\Services\PendenciasAssinaturaService::class)) {
            return [];
        }

        try {
            return app(\iEducar\Packages\BuritiAssinaturaDigital\Services\PendenciasAssinaturaService::class)
                ->listarParaUsuario($user, 80);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Catálogo de documentos já assinados para o painel (filtros + paginação no cliente).
     *
     * @return array{
     *   itens:list<array<string,mixed>>,
     *   catalogo:list<array<string,mixed>>,
     *   totais:array{todos:int,andamento:int,prontos:int},
     *   modulos:list<array{chave:string,rotulo:string}>,
     *   por_pagina:int
     * }
     */
    private function assinaturasAssinadas(User $user): array
    {
        $vazio = [
            'itens' => [],
            'catalogo' => [],
            'totais' => ['todos' => 0, 'andamento' => 0, 'prontos' => 0],
            'modulos' => [],
            'por_pagina' => 5,
        ];

        if (! class_exists(\iEducar\Packages\BuritiAssinaturaDigital\Services\PendenciasAssinaturaService::class)) {
            return $vazio;
        }

        try {
            $lista = app(\iEducar\Packages\BuritiAssinaturaDigital\Services\PendenciasAssinaturaService::class)
                ->listarAssinadosParaUsuario($user, 80, [
                    'filtro' => 'todos',
                    'por_pagina' => 100,
                    'pagina' => 1,
                    'forcar_filtro' => true,
                ]);

            $catalogo = array_values($lista['itens'] ?? []);
            $modulosMap = [];
            foreach ($catalogo as $item) {
                $chave = (string) ($item['modulo'] ?? 'outros');
                if ($chave === '' || isset($modulosMap[$chave])) {
                    continue;
                }
                $modulosMap[$chave] = [
                    'chave' => $chave,
                    'rotulo' => (string) ($item['modulo_rotulo'] ?? $chave),
                ];
            }
            uasort($modulosMap, static fn (array $a, array $b): int => strcmp($a['rotulo'], $b['rotulo']));

            // Destaque inicial: andamento primeiro, até por_pagina.
            $andamento = array_values(array_filter($catalogo, static fn (array $i): bool => ($i['estado'] ?? '') === 'parcial'));
            $prontos = array_values(array_filter($catalogo, static fn (array $i): bool => ($i['estado'] ?? '') === 'completo'));
            $itens = array_slice(array_merge($andamento, $prontos), 0, 5);

            return [
                'itens' => $itens,
                'catalogo' => $catalogo,
                'totais' => $lista['totais'] ?? $vazio['totais'],
                'modulos' => array_values($modulosMap),
                'por_pagina' => 5,
            ];
        } catch (\Throwable) {
            return $vazio;
        }
    }

    /**
     * Indicadores de qualidade no escopo permitido ao usuário.
     *
     * @param  list<array{titulo:string,descricao:?string,url:string,icone:string,externo:bool}>  $atalhos
     * @param  list<int>|null  $escolas
     * @return list<array{rotulo:string,valor:string|int,detalhe:?string,detalhe_extra:?string,tom:string,icone:string,url:?string,grupo:string,ajuda:?string}>
     */
    public function indicadores(User $user, int $naoLidas, array $atalhos = [], ?array $escolas = null): array
    {
        $escolas ??= $this->escolasEscopo($user);
        $ano = (int) date('Y');
        $escopoLabel = $escolas === null ? 'Rede' : 'Seu escopo';
        $kpis = [];

        $kpis[] = $this->kpiItem(
            'operacao',
            'Notificações abertas',
            $naoLidas,
            $naoLidas === 0 ? 'Caixa em dia' : 'Requer leitura',
            $naoLidas === 0 ? 'ok' : 'alerta',
            'fa-bell',
            url('/notificacoes'),
            ajuda: 'Conta notificações do usuário ainda sem leitura (read_at nulo).'
        );

        if ($this->podeVerEscola($user)) {
            $nEscolas = $this->contarEscolasAtivas($escolas);
            $alunos = $this->contarAlunosAtivos($escolas);
            $matriculas = $this->contarMatriculasAno($escolas, $ano);
            $turmas = $this->contarTurmasAtivas($escolas, $ano);
            $semTurma = $this->contarMatriculasSemTurma($escolas, $ano);
            $foraEscola = $this->contarAlunosAtivosSemMatriculaAno($escolas, $ano);
            $servidores = $this->contarServidoresAtivos($escolas);
            $profsTurma = $this->contarProfessoresComTurma($escolas, $ano);
            $taxaEnt = $matriculas > 0 ? (int) round((($matriculas - $semTurma) / $matriculas) * 100) : 100;
            $mediaTurma = $turmas > 0 ? round($matriculas / $turmas, 1) : 0;

            $kpis[] = $this->kpiItem(
                'rede',
                'Escolas ativas',
                $nEscolas,
                $escopoLabel,
                $nEscolas > 0 ? 'ok' : 'alerta',
                'fa-university',
                url('/intranet/educar_escola_lst.php'),
                ajuda: 'Escolas com cadastro ativo no escopo do seu perfil (escola.ativo = 1).'
            );
            $kpis[] = $this->kpiItem(
                'rede',
                'Alunos ativos',
                $this->fmt($alunos),
                $escopoLabel,
                $alunos > 0 ? 'ok' : 'alerta',
                'fa-users',
                url('/intranet/educar_aluno_lst.php'),
                ajuda: 'Cadastros com aluno.ativo = 1. Não exige matrícula no ano letivo atual — por isso o total pode ser maior que o de matrículas.'
            );
            $kpis[] = $this->kpiItem(
                'rede',
                "Matrículas {$ano}",
                $this->fmt($matriculas),
                'Ativas no ano letivo',
                $matriculas > 0 ? 'ok' : 'alerta',
                'fa-id-card',
                url('/intranet/educar_matricula_lst.php'),
                ajuda: "Matrículas ativas (matricula.ativo = 1) do ano letivo {$ano} no seu escopo. É o denominador da taxa de enturmação."
            );
            $kpis[] = $this->kpiItem(
                'rede',
                'Turmas ativas',
                $this->fmt($turmas),
                "Ano letivo {$ano}",
                $turmas > 0 ? 'ok' : 'alerta',
                'fa-sitemap',
                url('/intranet/educar_turma_lst.php'),
                ajuda: "Turmas com turma.ativo = 1 no ano letivo {$ano}, no escopo permitido."
            );
            $kpis[] = $this->kpiItem(
                'qualidade',
                'Taxa de enturmação',
                $taxaEnt.'%',
                $semTurma.' fora de turma',
                $taxaEnt >= 98 ? 'ok' : ($taxaEnt >= 90 ? 'alerta' : 'erro'),
                'fa-pie-chart',
                url('/intranet/educar_matricula_lst.php'),
                detalheExtra: $this->fmt($foraEscola).' fora de escola',
                ajuda: "Calculada só sobre as matrículas do ano: (matrículas − sem turma) ÷ matrículas."
                    ." «Fora de turma» = matrículas ativas de {$ano} sem vínculo ativo em turma."
                    ." «Fora de escola» = alunos ativos sem matrícula no ano {$ano} — entram no KPI de alunos, mas não no denominador da taxa."
            );
            $kpis[] = $this->kpiItem(
                'qualidade',
                'Média alunos/turma',
                number_format((float) $mediaTurma, 1, ',', '.'),
                'Matrículas ÷ turmas',
                $mediaTurma > 0 ? 'info' : 'alerta',
                'fa-balance-scale',
                null,
                ajuda: "Matrículas ativas de {$ano} divididas pelas turmas ativas do mesmo ano (média bruta do escopo)."
            );
            $kpis[] = $this->kpiItem(
                'qualidade',
                'Sem enturmação',
                $this->fmt($semTurma),
                'Matrículas ativas fora de turma',
                $semTurma === 0 ? 'ok' : 'erro',
                'fa-exclamation-circle',
                url('/intranet/educar_matricula_lst.php'),
                ajuda: "Matrículas ativas de {$ano} sem registro ativo em matricula_turma. É o numerador complementar da taxa de enturmação."
            );
            $kpis[] = $this->kpiItem(
                'rede',
                'Servidores',
                $this->fmt($servidores),
                'RH educacional',
                $servidores > 0 ? 'ok' : 'alerta',
                'fa-briefcase',
                url('/rh-educacional/servidores'),
                ajuda: 'Servidores ativos (pmieducar.servidor.ativo = 1) no escopo do perfil.'
            );
            $kpis[] = $this->kpiItem(
                'qualidade',
                'Docentes em turma',
                $this->fmt($profsTurma),
                "Vínculos {$ano}",
                $profsTurma > 0 ? 'ok' : 'alerta',
                'fa-graduation-cap',
                url('/rh-educacional/docentes'),
                ajuda: "Docentes distintos em modules.professor_turma no ano letivo {$ano} (mesmo critério da tela aberta pelo card)."
            );
        }

        // Contadores dos cards = pendências/ações (estilo notificação), não catálogo bruto.
        if ($this->atalhoPermite($atalhos, '/transporte')) {
            $rotas = $this->contarTabelaOpcional('transporte_escolar.rota', 'ativo');
            $kpis[] = $this->kpiItem(
                'modulos',
                'Rotas ativas',
                $rotas,
                $rotas === 0 ? 'Nenhuma rota cadastrada' : 'Rotas em operação',
                $rotas > 0 ? 'info' : 'alerta',
                'fa-bus',
                url('/transporte-escolar')
            );
        }
        if ($this->atalhoPermite($atalhos, '/alimentacao')) {
            $restricoes = $this->contarTabelaOpcional('alimentacao.restricao_aluno');
            $kpis[] = $this->kpiItem(
                'modulos',
                'Restrições',
                $restricoes,
                $restricoes === 0 ? 'Sem restrições ativas' : 'Alunos com restrição alimentar',
                $restricoes === 0 ? 'ok' : 'alerta',
                'fa-exclamation-triangle',
                url('/alimentacao-escolar/restricoes')
            );
        }
        if ($this->atalhoPermite($atalhos, '/almoxarifado')) {
            // Balão = pedidos em andamento (não catálogo/escola/auditoria da poc_registro).
            $pedidos = $this->contarAlmoxPedidosPendentes();
            $kpis[] = $this->kpiItem(
                'modulos',
                'Pedidos',
                $pedidos,
                $pedidos === 0 ? 'Nenhum pedido em andamento' : 'Pedidos aguardando separação/entrega',
                $pedidos === 0 ? 'ok' : 'alerta',
                'fa-shopping-cart',
                url('/almoxarifado-escolar/pedidos')
            );
        }
        if ($this->atalhoPermite($atalhos, '/solicitacao') || $this->atalhoPermite($atalhos, 'chamado')) {
            $chamados = $this->contarChamadosAbertos();
            $kpis[] = $this->kpiItem(
                'modulos',
                'Abertos',
                $chamados,
                $chamados === 0 ? 'Nenhum chamado em aberto' : 'Chamados aguardando atendimento',
                $chamados === 0 ? 'ok' : 'alerta',
                'fa-ticket',
                url('/solicitacao-servicos/abertos')
            );
        }
        if ($this->atalhoPermite($atalhos, '/pre-matricula') || $this->atalhoPermite($atalhos, 'vagas')) {
            $fila = $this->contarVagasPendentes();
            $kpis[] = $this->kpiItem(
                'modulos',
                'Fila',
                $fila,
                $fila === 0 ? 'Fila de vagas vazia' : 'Inscrições aguardando (fila/convocação/confirmação)',
                $fila === 0 ? 'ok' : 'alerta',
                'fa-share-square-o',
                url('/pre-matricula')
            );
        }
        if ($this->atalhoPermite($atalhos, '/biblioteca')) {
            $emprestimos = $this->contarPocTipo('biblioteca.poc_registro', 'emprestimo');
            $kpis[] = $this->kpiItem(
                'modulos',
                'Empréstimos',
                $emprestimos,
                $emprestimos === 0 ? 'Nenhum empréstimo aberto' : 'Empréstimos registrados',
                $emprestimos > 0 ? 'info' : 'ok',
                'fa-book',
                url('/biblioteca-escolar')
            );
        }
        if ($this->atalhoPermite($atalhos, '/atendimento')) {
            $fila = $this->contarFilaAtendimentoAberta();
            $kpis[] = $this->kpiItem(
                'modulos',
                'Na fila',
                $fila,
                $fila === 0 ? 'Fila vazia' : 'Aguardando atendimento',
                $fila === 0 ? 'ok' : 'alerta',
                'fa-handshake-o',
                url('/atendimento-estudante/fila')
            );
        }
        if ($this->atalhoPermite($atalhos, '/busca-ativa') || $this->atalhoPermite($atalhos, 'busca-ativa')) {
            $alertas = $this->contarBuscaAtivaAbertos();
            $kpis[] = $this->kpiItem(
                'modulos',
                'Alertas',
                $alertas,
                $alertas === 0 ? 'Sem alertas abertos' : 'Infrequência em aberto',
                $alertas === 0 ? 'ok' : 'erro',
                'fa-bell',
                url('/busca-ativa/alertas')
            );
        }
        if ($this->atalhoPermite($atalhos, '/horario')) {
            $aulas = $this->contarPocTipo('horario.poc_registro', 'aula');
            $kpis[] = $this->kpiItem(
                'modulos',
                'Aulas',
                $aulas,
                $aulas > 0 ? 'No quadro de horários' : 'Quadro ainda vazio',
                $aulas > 0 ? 'info' : 'alerta',
                'fa-calendar',
                url('/horario-automatico')
            );
        }
        if ($this->atalhoPermite($atalhos, '/apps-api') || $this->atalhoPermite($atalhos, 'apps-api')) {
            $tokens = 0;
            if (Schema::hasTable('personal_access_tokens')) {
                try {
                    $tokens = (int) DB::table('personal_access_tokens')->where('name', 'like', 'apps-%')->count();
                } catch (\Throwable) {
                    $tokens = 0;
                }
            }
            $kpis[] = $this->kpiItem(
                'modulos',
                'Tokens',
                $tokens,
                IdiarioPortal::integrado() || IdiarioPortal::disponivel() ? 'Apps da rede' : 'Sanctum apps',
                $tokens > 0 ? 'info' : 'alerta',
                'fa-mobile',
                url('/apps-api/governanca')
            );
        }

        $diarioOk = IdiarioPortal::integrado();
        $diarioUrl = IdiarioPortal::urlEntrada() ?? IdiarioPortal::url();
        if ($diarioUrl !== null || $this->atalhoPermite($atalhos, 'diario') || $this->podeVerEscola($user)) {
            $kpis[] = $this->kpiItem(
                'operacao',
                'i-Diário',
                $diarioOk ? 'Integrado' : ($diarioUrl ? 'Portal' : 'Off'),
                $diarioOk ? 'url + token OK' : ($diarioUrl ? 'Portal disponível' : 'Configure a integração'),
                $diarioOk ? 'ok' : ($diarioUrl ? 'alerta' : 'erro'),
                'fa-book',
                $diarioUrl
            );
        }

        return $kpis;
    }

    /**
     * @param  list<array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string,grupo:string}>  $kpis
     * @param  list<string>  $apenasGrupos
     * @return list<array{id:string,nome:string,icone:string,itens:list<array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string,grupo:string}>}>
     */
    private function agruparKpis(array $kpis, array $apenasGrupos = ['rede', 'qualidade']): array
    {
        $meta = [
            'rede' => ['nome' => 'Rede escolar', 'icone' => 'fa-university'],
            'qualidade' => ['nome' => 'Qualidade operacional', 'icone' => 'fa-line-chart'],
        ];
        $buckets = [];
        foreach ($kpis as $kpi) {
            $g = $kpi['grupo'] ?? '';
            if (! in_array($g, $apenasGrupos, true) || ! isset($meta[$g])) {
                continue;
            }
            $buckets[$g][] = $kpi;
        }
        $out = [];
        foreach ($meta as $id => $info) {
            if (empty($buckets[$id])) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'nome' => $info['nome'],
                'icone' => $info['icone'],
                'itens' => $buckets[$id],
            ];
        }

        return $out;
    }

    /**
     * Anexa KPIs do grupo "modulos" ao card — balões estilo notificação (só o que importa).
     *
     * @param  list<array<string,mixed>>  $atalhos
     * @param  list<array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string,grupo:string}>  $kpis
     * @return list<array<string,mixed>>
     */
    private function anexarContadoresAosModulos(array $atalhos, array $kpis): array
    {
        $modulosKpis = array_values(array_filter(
            $kpis,
            static fn (array $k): bool => ($k['grupo'] ?? '') === 'modulos'
        ));

        foreach ($atalhos as &$atalho) {
            $contadores = [];
            $urlModulo = mb_strtolower((string) ($atalho['url'] ?? ''));
            $titulo = mb_strtolower((string) ($atalho['titulo'] ?? ''));
            foreach ($modulosKpis as $kpi) {
                $urlKpi = mb_strtolower((string) ($kpi['url'] ?? ''));
                if ($urlKpi === '' || ! $this->urlsDoMesmoModulo($urlModulo, $urlKpi, $titulo)) {
                    continue;
                }

                $valorBruto = $kpi['valor'];
                $numerico = is_numeric($valorBruto) ? (float) $valorBruto : null;
                $tom = (string) ($kpi['tom'] ?? 'info');

                // Zero “ok” não vira balão; alerta/erro com zero também some (nada pendente).
                if ($numerico !== null && $numerico <= 0 && in_array($tom, ['ok', 'info', 'alerta', 'erro'], true)) {
                    if ($tom === 'ok' || $tom === 'info') {
                        continue;
                    }
                    // alerta/erro com 0: só mostra se for status textual (não numérico) — aqui some
                    continue;
                }

                $contadores[] = [
                    'rotulo' => $kpi['rotulo'],
                    'valor' => $valorBruto,
                    'detalhe' => $kpi['detalhe'] ?? null,
                    'tom' => $tom,
                    'url' => $kpi['url'],
                    'icone' => $kpi['icone'] ?? 'fa-circle',
                    'destaque' => in_array($tom, ['alerta', 'erro'], true),
                ];
            }

            usort($contadores, static function (array $a, array $b): int {
                $peso = static fn (array $c): int => match ($c['tom'] ?? 'info') {
                    'erro' => 0,
                    'alerta' => 1,
                    'info' => 2,
                    default => 3,
                };

                return $peso($a) <=> $peso($b);
            });

            $atalho['contadores'] = array_slice($contadores, 0, 2);
        }
        unset($atalho);

        return $atalhos;
    }

    private function urlsDoMesmoModulo(string $urlModulo, string $urlKpi, string $tituloModulo = ''): bool
    {
        $pathMod = rtrim((string) (parse_url($urlModulo, PHP_URL_PATH) ?: $urlModulo), '/') ?: '/';
        $pathKpi = rtrim((string) (parse_url($urlKpi, PHP_URL_PATH) ?: $urlKpi), '/') ?: '/';

        if ($pathMod === $pathKpi || str_starts_with($pathKpi, $pathMod.'/') || str_starts_with($pathMod, $pathKpi.'/')) {
            return true;
        }

        $pares = [
            ['transporte', 'transporte'],
            ['alimentacao', 'aliment'],
            ['almoxarifado', 'almox'],
            ['biblioteca', 'biblioteca'],
            ['atendimento', 'atendimento'],
            ['busca-ativa', 'busca-ativa'],
            ['busca ativa', 'busca-ativa'],
            ['horario', 'horario'],
            ['apps-api', 'apps'],
            ['diario', 'diario'],
            ['contratacoes', 'contrat'],
            ['pre-matricula', 'pre-matricula'],
            ['vagas', 'pre-matricula'],
            ['solicitacao', 'solicitacao'],
            ['chamado', 'solicitacao'],
            ['chamados', 'solicitacao'],
        ];
        foreach ($pares as [$needleMod, $needleKpi]) {
            $bateModulo = str_contains($pathMod, $needleMod) || str_contains($tituloModulo, $needleMod) || str_contains($tituloModulo, $needleKpi);
            $bateKpi = str_contains($pathKpi, $needleKpi) || str_contains($pathKpi, $needleMod);
            if ($bateModulo && $bateKpi) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{rotulo:string,valor:string|int,detalhe:?string,detalhe_extra:?string,tom:string,icone:string,url:?string,grupo:string,ajuda:?string}
     */
    private function kpiItem(
        string $grupo,
        string $rotulo,
        string|int $valor,
        ?string $detalhe,
        string $tom,
        string $icone,
        ?string $url,
        ?string $detalheExtra = null,
        ?string $ajuda = null,
    ): array {
        return [
            'grupo' => $grupo,
            'rotulo' => $rotulo,
            'valor' => $valor,
            'detalhe' => $detalhe,
            'detalhe_extra' => $detalheExtra,
            'tom' => $tom,
            'icone' => $icone,
            'url' => $url,
            'ajuda' => $ajuda,
        ];
    }

    private function fmt(int $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    /** @return list<int>|null null = sem restrição de escola */
    private function escolasEscopo(User $user): ?array
    {
        try {
            if (! method_exists($user, 'isSchooling') || ! $user->isSchooling()) {
                return null;
            }

            return $user->schools()
                ->pluck('cod_escola')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function podeVerEscola(User $user): bool
    {
        try {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
            if (method_exists($user, 'isInstitutional') && $user->isInstitutional()) {
                return true;
            }
            if (method_exists($user, 'isSchooling') && $user->isSchooling()) {
                return true;
            }
        } catch (\Throwable) {
            //
        }

        return true;
    }

    /** @param  list<int>|null  $escolas */
    private function contarEscolasAtivas(?array $escolas): int
    {
        if (! Schema::hasTable('pmieducar.escola')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.escola')->where('ativo', 1);
            if ($escolas !== null) {
                if ($escolas === []) {
                    return 0;
                }
                $q->whereIn('cod_escola', $escolas);
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param  list<int>|null  $escolas */
    private function contarServidoresAtivos(?array $escolas): int
    {
        if (! Schema::hasTable('pmieducar.servidor')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.servidor')->where('ativo', 1);
            if ($escolas !== null) {
                if ($escolas === [] || ! Schema::hasTable('pmieducar.servidor_alocacao')) {
                    return $escolas === [] ? 0 : (int) $q->count();
                }
                $q->whereIn('cod_servidor', function ($sub) use ($escolas) {
                    $sub->select('ref_cod_servidor')
                        ->from('pmieducar.servidor_alocacao')
                        ->where('ativo', 1)
                        ->whereIn('ref_cod_escola', $escolas);
                });
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param  list<int>|null  $escolas */
    private function contarProfessoresComTurma(?array $escolas, int $ano): int
    {
        if (! Schema::hasTable('modules.professor_turma')) {
            return 0;
        }
        try {
            $q = DB::table('modules.professor_turma')->where('ano', $ano);
            if ($escolas !== null) {
                if ($escolas === [] || ! Schema::hasTable('pmieducar.turma')) {
                    return 0;
                }
                $q->whereIn('turma_id', function ($sub) use ($escolas, $ano) {
                    $sub->select('cod_turma')
                        ->from('pmieducar.turma')
                        ->where('ativo', 1)
                        ->where('ano', $ano)
                        ->whereIn('ref_ref_cod_escola', $escolas);
                });
            }

            return (int) $q->distinct()->count('servidor_id');
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param  list<int>|null  $escolas */
    private function contarAlunosAtivos(?array $escolas): int
    {
        if (! Schema::hasTable('pmieducar.aluno')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.aluno')->where('ativo', 1);
            if ($escolas !== null) {
                if ($escolas === [] || ! Schema::hasTable('pmieducar.matricula')) {
                    return 0;
                }
                $q->whereIn('cod_aluno', function ($sub) use ($escolas) {
                    $sub->select('ref_cod_aluno')
                        ->from('pmieducar.matricula')
                        ->where('ativo', 1)
                        ->whereIn('ref_ref_cod_escola', $escolas);
                });
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param  list<int>|null  $escolas */
    private function contarMatriculasAno(?array $escolas, int $ano): int
    {
        if (! Schema::hasTable('pmieducar.matricula')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.matricula')->where('ativo', 1)->where('ano', $ano);
            if ($escolas !== null) {
                if ($escolas === []) {
                    return 0;
                }
                $q->whereIn('ref_ref_cod_escola', $escolas);
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param  list<int>|null  $escolas */
    private function contarTurmasAtivas(?array $escolas, int $ano): int
    {
        if (! Schema::hasTable('pmieducar.turma')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.turma')->where('ativo', 1)->where('ano', $ano);
            if ($escolas !== null) {
                if ($escolas === []) {
                    return 0;
                }
                $q->whereIn('ref_ref_cod_escola', $escolas);
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @param  list<int>|null  $escolas */
    private function contarMatriculasSemTurma(?array $escolas, int $ano): int
    {
        if (! Schema::hasTable('pmieducar.matricula') || ! Schema::hasTable('pmieducar.matricula_turma')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.matricula as m')
                ->where('m.ativo', 1)
                ->where('m.ano', $ano)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('pmieducar.matricula_turma as mt')
                        ->whereColumn('mt.ref_cod_matricula', 'm.cod_matricula')
                        ->where('mt.ativo', 1);
                });
            if ($escolas !== null) {
                if ($escolas === []) {
                    return 0;
                }
                $q->whereIn('m.ref_ref_cod_escola', $escolas);
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Alunos ativos sem matrícula ativa no ano letivo (estão no KPI de alunos, mas fora do denominador da enturmação).
     *
     * @param  list<int>|null  $escolas
     */
    private function contarAlunosAtivosSemMatriculaAno(?array $escolas, int $ano): int
    {
        if (! Schema::hasTable('pmieducar.aluno') || ! Schema::hasTable('pmieducar.matricula')) {
            return 0;
        }
        try {
            $q = DB::table('pmieducar.aluno as a')
                ->where('a.ativo', 1)
                ->whereNotExists(function ($sub) use ($ano, $escolas) {
                    $sub->select(DB::raw(1))
                        ->from('pmieducar.matricula as m')
                        ->whereColumn('m.ref_cod_aluno', 'a.cod_aluno')
                        ->where('m.ativo', 1)
                        ->where('m.ano', $ano);
                    if ($escolas !== null) {
                        $sub->whereIn('m.ref_ref_cod_escola', $escolas);
                    }
                });

            if ($escolas !== null) {
                if ($escolas === []) {
                    return 0;
                }
                // Mesmo critério de "alunos ativos" no escopo: já teve matrícula ativa nessas escolas.
                $q->whereIn('a.cod_aluno', function ($sub) use ($escolas) {
                    $sub->select('ref_cod_aluno')
                        ->from('pmieducar.matricula')
                        ->where('ativo', 1)
                        ->whereIn('ref_ref_cod_escola', $escolas);
                });
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @param  list<array{url:string}>  $atalhos
     */
    private function atalhoPermite(array $atalhos, string $trecho): bool
    {
        $trecho = mb_strtolower($trecho);
        foreach ($atalhos as $a) {
            if (str_contains(mb_strtolower($a['url']), $trecho) || str_contains(mb_strtolower($a['titulo'] ?? ''), $trecho)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{rotulo:string,valor:string|int,detalhe:?string,tom:string,icone:string,url:?string}
     */
    private function kpiSchemaCount(string $table, string $rotulo, string $icone, ?string $url, ?string $ativoCol = null): array
    {
        $valor = $this->contarTabelaOpcional($table, $ativoCol);
        $tom = $valor > 0 ? 'ok' : 'alerta';

        return [
            'rotulo' => $rotulo,
            'valor' => $valor,
            'detalhe' => 'No seu escopo de sistemas',
            'tom' => $tom,
            'icone' => $icone,
            'url' => $url,
        ];
    }

    private function contarTabelaOpcional(string $table, ?string $ativoCol = null): int
    {
        try {
            if (! Schema::hasTable($table)) {
                return 0;
            }
            $q = DB::table($table);
            if ($ativoCol !== null && Schema::hasColumn($table, $ativoCol)) {
                $q->where($ativoCol, true);
            }

            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function contarPocTipo(string $table, string $tipo): int
    {
        try {
            if (! Schema::hasTable($table)) {
                return 0;
            }

            return (int) DB::table($table)->where('tipo', $tipo)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function contarFilaAtendimentoAberta(): int
    {
        try {
            if (! Schema::hasTable('atendimento.poc_registro')) {
                return 0;
            }
            $rows = DB::table('atendimento.poc_registro')
                ->where('tipo', 'fila')
                ->orderByDesc('id')
                ->limit(200)
                ->get(['payload']);
            $abertos = 0;
            foreach ($rows as $row) {
                $p = is_string($row->payload ?? null)
                    ? (json_decode($row->payload, true) ?: [])
                    : (array) ($row->payload ?? []);
                $status = mb_strtolower((string) ($p['status'] ?? 'fila'));
                if (in_array($status, ['atendido', 'finalizado', 'cancelado', 'encerrado'], true)) {
                    continue;
                }
                $abertos++;
            }

            return $abertos;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function contarBuscaAtivaAbertos(): int
    {
        try {
            if (! Schema::hasTable('busca_ativa.alerta')) {
                return 0;
            }

            return (int) DB::table('busca_ativa.alerta')->where('status', 'aberto')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Pedidos de almoxarifado ainda em fluxo (exclui rascunho carrinho e finalizados).
     */
    private function contarAlmoxPedidosPendentes(): int
    {
        try {
            if (! Schema::hasTable('almoxarifado.poc_registro')) {
                return 0;
            }
            $finalizados = ['entregue', 'conferido', 'carrinho'];
            $n = 0;
            foreach (DB::table('almoxarifado.poc_registro')->where('tipo', 'pedido')->orderByDesc('id')->limit(500)->get(['payload', 'meta']) as $row) {
                $p = is_string($row->payload ?? null)
                    ? (json_decode($row->payload, true) ?: [])
                    : (array) ($row->payload ?? []);
                $status = mb_strtolower(trim((string) ($p['status'] ?? $row->meta ?? '')));
                if ($status === '' || in_array($status, $finalizados, true)) {
                    continue;
                }
                $n++;
            }

            return $n;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Chamados em aberto / triagem / atendimento / aguardando. */
    private function contarChamadosAbertos(): int
    {
        try {
            if (! Schema::hasTable('solicitacao_servicos.poc_registro')) {
                return 0;
            }
            $abertos = ['aberto', 'triagem', 'em_atendimento', 'aguardando', 'em_andamento'];
            $n = 0;
            foreach (DB::table('solicitacao_servicos.poc_registro')->where('tipo', 'chamado')->orderByDesc('id')->limit(500)->get(['payload', 'meta']) as $row) {
                $p = is_string($row->payload ?? null)
                    ? (json_decode($row->payload, true) ?: [])
                    : (array) ($row->payload ?? []);
                $sit = mb_strtolower(trim((string) ($p['situacao'] ?? '')));
                if ($sit === '' && is_string($row->meta ?? null)) {
                    foreach ($abertos as $a) {
                        if (str_contains(mb_strtolower((string) $row->meta), $a)) {
                            $sit = $a;
                            break;
                        }
                    }
                }
                if (in_array($sit, $abertos, true)) {
                    $n++;
                }
            }

            return $n;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Inscrições de vagas que pedem ação: fila (1), convocadas (4) e em confirmação (5).
     */
    private function contarVagasPendentes(): int
    {
        try {
            if (! Schema::hasTable('preregistrations')) {
                return 0;
            }

            return (int) DB::table('preregistrations')
                ->whereIn('status', [1, 4, 5])
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{
     *   nome:string,
     *   login:?string,
     *   email:?string,
     *   tipo:?string,
     *   nivel:?string,
     *   ativo:bool,
     *   escolas:list<string>,
     *   data_cadastro:?string,
     *   links:list<array{rotulo:string,url:string,icone:string}>
     * }
     */
    public function perfil(User $user): array
    {
        $escolas = [];
        try {
            if (method_exists($user, 'isSchooling') && $user->isSchooling()) {
                $escolas = $user->schools()
                    ->get()
                    ->map(static function ($escola): string {
                        try {
                            $nome = (string) ($escola->name ?? '');
                        } catch (\Throwable) {
                            $nome = '';
                        }

                        return $nome !== '' ? $nome : ('Escola #'.$escola->getKey());
                    })
                    ->filter()
                    ->values()
                    ->all();
            }
        } catch (\Throwable) {
            $escolas = [];
        }

        $nivel = null;
        try {
            $nivel = match ((int) ($user->type->level ?? 0)) {
                LegacyUserType::LEVEL_ADMIN => 'Poli-institucional',
                LegacyUserType::LEVEL_INSTITUTIONAL => 'Institucional',
                LegacyUserType::LEVEL_SCHOOLING => 'Escola',
                LegacyUserType::LEVEL_LIBRARY => 'Biblioteca',
                default => $user->type->nm_tipo ?? null,
            };
        } catch (\Throwable) {
            $nivel = $user->type->nm_tipo ?? null;
        }

        $email = null;
        $ativo = true;
        $dataCadastro = null;
        try {
            $email = $user->email ? (string) $user->email : null;
            $ativo = method_exists($user, 'isActive') ? $user->isActive() : (bool) ($user->ativo ?? true);
            $rawCad = $user->employee->data_cad ?? null;
            if ($rawCad !== null && $rawCad !== '') {
                $dataCadastro = $rawCad instanceof Carbon
                    ? $rawCad->format('d/m/Y')
                    : Carbon::parse((string) $rawCad)->format('d/m/Y');
            }
        } catch (\Throwable) {
            //
        }

        return [
            'nome' => (string) ($user->name ?? $user->login ?? 'Usuário'),
            'login' => $user->login ?? null,
            'email' => $email,
            'tipo' => $user->type->nm_tipo ?? null,
            'nivel' => $nivel,
            'ativo' => $ativo,
            'escolas' => $escolas,
            'data_cadastro' => $dataCadastro,
            'links' => array_filter([
                [
                    'rotulo' => 'Alterar senha',
                    'url' => route('change-password'),
                    'icone' => 'fa-lock',
                ],
                class_exists(\iEducar\Packages\BuritiSeguranca\Providers\SegurancaServiceProvider::class) ? [
                    'rotulo' => 'Segurança',
                    'url' => url('/seguranca/minha'),
                    'icone' => 'fa-shield',
                ] : null,
                [
                    'rotulo' => 'Meus dados (completo)',
                    'url' => url('/intranet/meusdados.php'),
                    'icone' => 'fa-id-card',
                ],
                [
                    'rotulo' => 'Agenda pessoal',
                    'url' => url('/intranet/agenda.php'),
                    'icone' => 'fa-calendar',
                ],
            ]),
        ];
    }

    private function iniciais(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome)) ?: [];
        $letras = [];
        foreach ($partes as $p) {
            if ($p === '') {
                continue;
            }
            $letras[] = mb_strtoupper(mb_substr($p, 0, 1));
            if (count($letras) >= 2) {
                break;
            }
        }

        return $letras !== [] ? implode('', $letras) : 'U';
    }

    /**
     * @return list<array{id:int,texto:string,link:?string,lida:bool,quando:?string}>
     */
    private function notificacoes(User $user): array
    {
        return Notification::query()
            ->where('user_id', $user->getKey())
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(static fn (Notification $n): array => [
                'id' => (int) $n->getKey(),
                'texto' => (string) $n->text,
                'link' => $n->link ? (string) $n->link : null,
                'lida' => $n->read_at !== null,
                'quando' => $n->created_at
                    ? (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\DataHora::class)
                        ? \iEducar\Packages\BuritiLagunaAmbiente\Support\DataHora::formatar($n->created_at)
                        : $n->created_at->locale('pt_BR')->diffForHumans())
                    : null,
            ])
            ->all();
    }

    /**
     * @param  list<int>|null  $escolas
     * @return list<array{titulo:string,detalhe:string,tom:string,url:?string,icone:string}>
     */
    /**
     * @param  array{avisos?:list<array{titulo:string,detalhe:string,tom:string,url:string,icone:string}>}|null  $seguranca
     */
    private function atencoes(User $user, int $naoLidas, ?array $escolas = null, int $assinaturasPendentes = 0, ?array $seguranca = null): array
    {
        $itens = [];
        $ano = (int) date('Y');

        // Avisos MFA/senha ficam no lembrete junto ao card de boas-vindas — não na Caixa do dia.

        if ($assinaturasPendentes > 0) {
            $itens[] = [
                'titulo' => $assinaturasPendentes === 1
                    ? '1 documento pendente de assinatura'
                    : "{$assinaturasPendentes} documentos pendentes de assinatura",
                'detalhe' => 'Confirme sua assinatura digital no LibreSign.',
                'tom' => 'alerta',
                'url' => url('/assinatura-digital/pendencias'),
                'icone' => 'fa-pencil-square-o',
                'tipo' => 'atencao',
                'filtro' => 'assinatura',
            ];
        }

        if ($naoLidas > 0) {
            $itens[] = [
                'titulo' => $naoLidas === 1 ? '1 notificação não lida' : "{$naoLidas} notificações não lidas",
                'detalhe' => 'Há avisos pendentes na sua caixa de notificações.',
                'tom' => 'alerta',
                'url' => url('/notificacoes'),
                'icone' => 'fa-bell',
                'tipo' => 'notif',
                'filtro' => 'notif',
            ];
        }

        if ($this->podeVerEscola($user)) {
            $semTurma = $this->contarMatriculasSemTurma($escolas, $ano);
            if ($semTurma > 0) {
                $itens[] = [
                    'titulo' => $semTurma === 1 ? '1 matrícula sem enturmação' : "{$semTurma} matrículas sem enturmação",
                    'detalhe' => "Alunos com matrícula ativa em {$ano} ainda fora de turma.",
                    'tom' => $semTurma > 20 ? 'erro' : 'alerta',
                    'url' => url('/intranet/educar_matricula_lst.php'),
                    'icone' => 'fa-exclamation-circle',
                    'tipo' => 'atencao',
                    'filtro' => 'escola',
                ];
            }
        }

        $aviso = Announcement::query()
            ->whereHas('userTypes', fn ($q) => $q->whereKey($user->ref_cod_tipo_usuario))
            ->latest()
            ->first();

        if ($aviso !== null) {
            $lido = $aviso->users()
                ->whereKey($user->getKey())
                ->wherePivotNotNull('read_at')
                ->exists();
            if (! $lido || $aviso->repeat_on_login) {
                $itens[] = [
                    'titulo' => 'Aviso institucional pendente',
                    'detalhe' => (string) ($aviso->title ?? 'Há um comunicado da SME para leitura.'),
                    'tom' => 'info',
                    'url' => route('announcement.user.show'),
                    'icone' => 'fa-bullhorn',
                    'tipo' => 'atencao',
                    'filtro' => 'institucional',
                ];
            }
        }

        if ($itens === []) {
            $itens[] = [
                'titulo' => 'Nenhum ponto de atenção',
                'detalhe' => 'Sua conta está em dia — use os atalhos para entrar nos sistemas.',
                'tom' => 'ok',
                'url' => null,
                'icone' => 'fa-check-circle',
                'tipo' => 'ok',
                'filtro' => 'ok',
            ];
        }

        return $itens;
    }

    /**
     * @return list<array{titulo:string,descricao:?string,url:string,icone:string,externo:bool}>
     */
    private function atalhos(User $user): array
    {
        $atalhos = [];

        // i-Educar fica no botão do topo (logomarca); aqui só módulos disponíveis.

        $diario = IdiarioPortal::urlEntrada();
        if ($diario !== null) {
            $metaDiario = $this->metaJornadaModulo('i-Diário', $diario);
            $podeDiario = $user->isAdmin()
                || (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::class)
                    && \iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::usuarioPodeAcessarModulo($user, 'idiario'));
            $atalhos[] = [
                'titulo' => $this->tituloCardModulo('i-Diário', $diario),
                'descricao' => ! $podeDiario
                    ? 'Seu perfil não inclui este módulo.'
                    : (IdiarioPortal::integrado()
                        ? 'Frequência e lançamentos com SSO.'
                        : 'Frequência e lançamentos pedagógicos.'),
                'url' => $diario,
                'icone' => $metaDiario['icone'],
                'externo' => ! str_starts_with($diario, url('/')),
                'jornada' => $metaDiario['jornada'],
                'ordem' => $metaDiario['ordem'],
                'cor' => $metaDiario['cor'],
                'acao' => $metaDiario['acao'],
                'modulo_id' => 'idiario',
                'modulo_ativo' => true,
                'acesso_permitido' => $podeDiario,
            ];
        }

        /** @var Collection<int, Menu> $menus */
        $menus = collect($this->menus->getMenuByUser($user) ?? []);

        foreach ($menus as $menu) {
            if (! $menu instanceof Menu || ! $menu->hasLink()) {
                continue;
            }

            $titulo = trim((string) $menu->title);
            $url = $this->primeiraUrl($menu);
            if ($url === null || $this->ocultarModuloPainel($titulo, $url)) {
                continue;
            }

            $atalhos[] = $this->montarAtalhoModulo(
                $titulo,
                $url,
                $menu->description ? (string) $menu->description : null,
                $menu->icon ? (string) $menu->icon : null
            );
        }

        // Módulos-produto aninhados (ex.: Busca Ativa sob Escola) entram no painel.
        foreach ($menus as $menu) {
            if (! $menu instanceof Menu) {
                continue;
            }
            foreach ($this->coletarModulosPromovidos($menu) as $promovido) {
                $atalhos[] = $promovido;
            }
        }

        $unicos = $this->unicosPorUrl($atalhos);
        $unicos = $this->anexarStatusModulos($unicos);
        $unicos = $this->marcarAcessoPermitido($user, $unicos);
        $unicos = $this->incluirModulosCatalogoNoPainel($user, $unicos);
        $unicos = $this->incluirNucleoPainelSemAcesso($user, $unicos);
        usort($unicos, static function (array $a, array $b): int {
            // Sem acesso / desligados no fim; usáveis primeiro (ordem de jornada).
            $usable = static fn (array $x): bool => (! array_key_exists('acesso_permitido', $x) || ! empty($x['acesso_permitido']))
                && (! array_key_exists('modulo_ativo', $x) || ! empty($x['modulo_ativo']));
            $cmpUsavel = ((int) $usable($b)) <=> ((int) $usable($a));
            if ($cmpUsavel !== 0) {
                return $cmpUsavel;
            }
            $cmp = ($a['ordem'] ?? 99) <=> ($b['ordem'] ?? 99);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($a['titulo'] ?? ''), (string) ($b['titulo'] ?? ''));
        });

        return $unicos;
    }

    /**
     * @return array{titulo:string,descricao:string,url:string,icone:string,externo:bool,jornada:string,ordem:int,cor:string,acao:string}
     */
    private function montarAtalhoModulo(string $titulo, string $url, ?string $descricaoMenu, ?string $iconeMenu = null): array
    {
        $meta = $this->metaJornadaModulo($titulo, $url);

        $urlAbs = $this->absolutizar($url);
        $moduloId = $this->resolverModuloIdPorUrl($urlAbs);

        return [
            'titulo' => $this->tituloCardModulo($titulo, $url),
            'descricao' => $this->descricaoModulo($titulo, $descricaoMenu, $url),
            'url' => $urlAbs,
            // Ícone da jornada prevalece — linguagem visual uniforme nos cards.
            'icone' => $meta['icone'] ?? ($iconeMenu ?: 'fa-th-large'),
            'externo' => str_starts_with($url, 'http'),
            'jornada' => $meta['jornada'],
            'ordem' => $meta['ordem'],
            'cor' => $meta['cor'],
            'acao' => $meta['acao'],
            'modulo_id' => $moduloId,
            'modulo_ativo' => $moduloId === null
                ? true
                : (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::class)
                    ? \iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::ativo($moduloId)
                    : true),
            'acesso_permitido' => true,
        ];
    }

    /**
     * Título curto do card: uma palavra-chave em maiúsculas (exceção: BUSCA ATIVA).
     */
    private function tituloCardModulo(string $titulo, string $url): string
    {
        $t = mb_strtolower(trim($titulo));
        $u = mb_strtolower($url);

        if (str_contains($t, 'busca ativa') || str_contains($u, 'busca-ativa')) {
            return 'BUSCA ATIVA';
        }

        $porUrl = [
            ['needle' => 'pre-matricula-digital', 'label' => 'VAGAS'],
            ['needle' => 'pre-matricula', 'label' => 'VAGAS'],
            ['needle' => '/vagas', 'label' => 'VAGAS'],
            ['needle' => 'diario', 'label' => 'I-DIÁRIO'],
            ['needle' => 'atendimento', 'label' => 'ATENDIMENTO'],
            ['needle' => 'biblioteca', 'label' => 'BIBLIOTECA'],
            ['needle' => 'horario', 'label' => 'HORÁRIOS'],
            ['needle' => 'transporte', 'label' => 'TRANSPORTE'],
            ['needle' => 'alimentacao', 'label' => 'ALIMENTAÇÃO'],
            ['needle' => 'almoxarifado', 'label' => 'ALMOXARIFADO'],
            ['needle' => 'eventos', 'label' => 'EVENTOS'],
            ['needle' => 'contratacoes', 'label' => 'CONTRATAÇÕES'],
            ['needle' => '/act', 'label' => 'CONTRATAÇÕES'],
            ['needle' => 'apps-api', 'label' => 'APP e Governança'],
            ['needle' => 'solicitacao', 'label' => 'CHAMADOS'],
            ['needle' => 'laguna', 'label' => 'MODELO'],
            ['needle' => 'educacenso', 'label' => 'EDUCACENSO'],
            ['needle' => 'advanced-reports', 'label' => 'RELATÓRIOS'],
            ['needle' => 'exportacao', 'label' => 'EXPORTAÇÃO'],
            ['needle' => 'pessoas', 'label' => 'PESSOAS'],
            ['needle' => 'servidor', 'label' => 'SERVIDORES'],
            ['needle' => 'configur', 'label' => 'CONFIGURAÇÕES'],
            ['needle' => 'assinatura-digital', 'label' => 'ASSINATURA'],
            ['needle' => 'busca-ativa', 'label' => 'BUSCA ATIVA'],
        ];
        foreach ($porUrl as $regra) {
            if (str_contains($u, $regra['needle'])) {
                return $regra['label'];
            }
        }

        $porTitulo = [
            ['needle' => 'pré-matrícula', 'label' => 'VAGAS'],
            ['needle' => 'pre-matricula', 'label' => 'VAGAS'],
            ['needle' => 'vagas', 'label' => 'VAGAS'],
            ['needle' => 'ambiente laguna', 'label' => 'MODELO'],
            ['needle' => 'laguna', 'label' => 'MODELO'],
            ['needle' => 'atendimento', 'label' => 'ATENDIMENTO'],
            ['needle' => 'biblioteca', 'label' => 'BIBLIOTECA'],
            ['needle' => 'horário', 'label' => 'HORÁRIOS'],
            ['needle' => 'horario', 'label' => 'HORÁRIOS'],
            ['needle' => 'quadro', 'label' => 'HORÁRIOS'],
            ['needle' => 'transporte', 'label' => 'TRANSPORTE'],
            ['needle' => 'aliment', 'label' => 'ALIMENTAÇÃO'],
            ['needle' => 'almox', 'label' => 'ALMOXARIFADO'],
            ['needle' => 'evento', 'label' => 'EVENTOS'],
            ['needle' => 'forma', 'label' => 'EVENTOS'],
            ['needle' => 'contrata', 'label' => 'CONTRATAÇÕES'],
            ['needle' => 'apps api', 'label' => 'APP e Governança'],
            ['needle' => 'apis', 'label' => 'APIS'],
            ['needle' => 'apps', 'label' => 'APIS'],
            ['needle' => 'solicit', 'label' => 'CHAMADOS'],
            ['needle' => 'chamado', 'label' => 'CHAMADOS'],
            ['needle' => 'educacenso', 'label' => 'EDUCACENSO'],
            ['needle' => 'censo', 'label' => 'EDUCACENSO'],
            ['needle' => 'advanced reports', 'label' => 'RELATÓRIOS'],
            ['needle' => 'relatórios avançados', 'label' => 'RELATÓRIOS'],
            ['needle' => 'relatorios avancados', 'label' => 'RELATÓRIOS'],
            ['needle' => 'export', 'label' => 'EXPORTAÇÃO'],
            ['needle' => 'i-diário', 'label' => 'I-DIÁRIO'],
            ['needle' => 'i-diario', 'label' => 'I-DIÁRIO'],
            ['needle' => 'pessoa', 'label' => 'PESSOAS'],
            ['needle' => 'servidor', 'label' => 'SERVIDORES'],
            ['needle' => 'escola', 'label' => 'ESCOLA'],
            ['needle' => 'assinatura', 'label' => 'ASSINATURA'],
            ['needle' => 'busca ativa', 'label' => 'BUSCA ATIVA'],
            ['needle' => 'configura', 'label' => 'CONFIGURAÇÕES'],
        ];
        // Preferir matches mais longos / específicos.
        usort($porTitulo, static fn (array $a, array $b): int => mb_strlen($b['needle']) <=> mb_strlen($a['needle']));
        foreach ($porTitulo as $regra) {
            if (str_contains($t, $regra['needle'])) {
                return $regra['label'];
            }
        }

        // Fallback: primeira palavra significativa em maiúsculas.
        $partes = preg_split('/\s+/u', trim($titulo)) ?: [];
        $palavra = '';
        foreach ($partes as $parte) {
            $limpa = trim($parte, " \t\n\r\0\x0B-–—");
            if ($limpa === '' || in_array(mb_strtolower($limpa), ['de', 'da', 'do', 'das', 'dos', 'e', 'ao', 'à'], true)) {
                continue;
            }
            $palavra = $limpa;
            break;
        }
        if ($palavra === '') {
            $palavra = $titulo !== '' ? $titulo : 'MÓDULO';
        }

        return mb_strtoupper($palavra, 'UTF-8');
    }

    /**
     * Filhos do menu que são módulos com jornada própria (não telas operacionais).
     *
     * @return list<array{titulo:string,descricao:string,url:string,icone:string,externo:bool,jornada:string,ordem:int,cor:string,acao:string}>
     */
    private function coletarModulosPromovidos(Menu $menu): array
    {
        $out = [];
        foreach ($menu->children ?? [] as $filho) {
            if (! $filho instanceof Menu) {
                continue;
            }

            $titulo = trim((string) $filho->title);
            $url = null;
            if ($filho->isLink() && is_string($filho->link) && $filho->link !== '') {
                $url = $filho->link;
            }

            if ($url !== null && $this->promoverModuloFilho($titulo, $url) && ! $this->ocultarModuloPainel($titulo, $url)) {
                $out[] = $this->montarAtalhoModulo(
                    $titulo,
                    $url,
                    $filho->description ? (string) $filho->description : null,
                    $filho->icon ? (string) $filho->icon : null
                );
            }

            foreach ($this->coletarModulosPromovidos($filho) as $neto) {
                $out[] = $neto;
            }
        }

        return $out;
    }

    private function promoverModuloFilho(string $titulo, string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $url;
        }
        $path = rtrim(mb_strtolower($path), '/') ?: '/';

        // Só a raiz do módulo — não subpáginas (conflitos, inscrições, etc.).
        $raizes = [
            '/busca-ativa',
            '/biblioteca-escolar',
            '/atendimento-estudante',
            '/rh-educacional',
            '/pre-matricula',
            '/pre-matricula-digital',
            '/pre-matricula-digital/vagas',
            '/apps-api/configuracao',
            '/apps-api',
        ];
        foreach ($raizes as $raiz) {
            if ($path === $raiz) {
                return true;
            }
        }

        $t = mb_strtolower(trim($titulo));
        $titulosExatos = [
            'busca ativa',
            'biblioteca',
            'biblioteca escolar',
            'atendimento ao estudante',
            'rh educacional',
            'rh / servidores',
            'servidores',
            'vagas',
            'pré-matrícula',
            'pre-matricula',
            'apps api',
        ];

        return in_array($t, $titulosExatos, true) && in_array($path, $raizes, true);
    }

    private function ocultarModuloPainel(string $titulo, string $url): bool
    {
        $t = mb_strtolower($titulo);
        $u = mb_strtolower($url);

        // Cadastro de logradouros/CEPs — não entra no rol de módulos do painel.
        if (str_contains($t, 'endereçamento') || str_contains($t, 'enderecamento')) {
            return true;
        }
        if (str_contains($u, 'enderecamento')) {
            return true;
        }

        // Agregados ao RH — não aparecem como módulos à parte.
        $agregados = [
            '/horario-automatico',
            '/contratacoes-temporarias',
            '/eventos-formacao',
        ];
        foreach ($agregados as $a) {
            if ($u === $a || str_starts_with($u, $a.'/')) {
                return true;
            }
        }

        return str_contains($t, 'quadro de hor')
            || str_contains($t, 'contrata')
            || (str_contains($t, 'eventos') && str_contains($t, 'forma'));
    }

    private function descricaoModulo(string $titulo, ?string $descricaoMenu, string $url): string
    {
        $chave = mb_strtolower($titulo);
        $catalogo = [
            'i-diário' => 'Frequência e lançamentos pedagógicos.',
            'pessoas' => 'Pessoas físicas e jurídicas da rede.',
            'escola' => 'Escolas, turmas, matrículas e enturmação.',
            'vagas' => 'Pré-matrícula digital e oferta de vagas.',
            'pré-matrícula' => 'Pré-matrícula digital e oferta de vagas.',
            'pre-matricula' => 'Pré-matrícula digital e oferta de vagas.',
            'servidores' => 'Cadastro, lotação e vínculos.',
            'transporte' => 'Rotas, frota e alunos transportados.',
            'alimentação' => 'Cardápio, restrições e merenda.',
            'alimentacao' => 'Cardápio, restrições e merenda.',
            'almoxarifado' => 'Estoque, pedidos e entregas.',
            'biblioteca' => 'Acervo e empréstimos.',
            'biblioteca escolar' => 'Acervo e empréstimos.',
            'atendimento' => 'Fila e acolhimento do estudante.',
            'busca ativa' => 'Infrequência e acompanhamento familiar.',
            'solicitação de serviços' => 'Chamados escola ↔ secretaria.',
            'solicitacao de serviços' => 'Chamados escola ↔ secretaria.',
            'educacenso' => 'Censo Escolar (INEP).',
            'configurações' => 'Parâmetros e administração.',
            'configuracoes' => 'Parâmetros e administração.',
            'segurança' => 'Políticas de senha, MFA, passkeys e sessões.',
            'seguranca' => 'Políticas de senha, MFA, passkeys e sessões.',
            'ambiente laguna' => 'Auditoria e setup da cidade modelo.',
            'quadro de horários' => 'Grade e alocação de aulas.',
            'quadro de horarios' => 'Grade e alocação de aulas.',
            'contratações' => 'Processos de contratação.',
            'contratações temporárias' => 'Editais e escolha de vagas ACT.',
            'eventos e formação' => 'Agenda e formação continuada.',
            'apps api' => 'APP e Governança (família e professor).',
        ];

        if (isset($catalogo[$chave])) {
            return $catalogo[$chave];
        }

        // Preferir nomes mais longos (ex.: "biblioteca escolar" antes de "escola").
        $nomes = array_keys($catalogo);
        usort($nomes, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($nomes as $nome) {
            if (str_contains($chave, $nome)) {
                return $catalogo[$nome];
            }
        }

        $descMenu = trim((string) $descricaoMenu);
        if ($descMenu !== '') {
            // Resumo curto para caber no card (sem reticências forçadas no CSS).
            if (mb_strlen($descMenu) > 56) {
                $corte = mb_substr($descMenu, 0, 56);
                $espaco = mb_strrpos($corte, ' ');
                $descMenu = ($espaco !== false ? mb_substr($corte, 0, $espaco) : $corte).'.';
            }

            return $descMenu;
        }

        $u = mb_strtolower($url);
        if (str_contains($u, 'transporte')) {
            return $catalogo['transporte'];
        }
        if (str_contains($u, 'alimentacao')) {
            return $catalogo['alimentação'];
        }
        if (str_contains($u, 'almoxarifado')) {
            return $catalogo['almoxarifado'];
        }
        if (str_contains($u, 'biblioteca')) {
            return $catalogo['biblioteca'];
        }
        if (str_contains($u, 'atendimento')) {
            return $catalogo['atendimento'];
        }
        if (str_contains($u, 'solicitacao')) {
            return $catalogo['solicitação de serviços'];
        }
        if (str_contains($u, 'educacenso')) {
            return $catalogo['educacenso'];
        }
        if (str_contains($u, 'configur')) {
            return $catalogo['configurações'];
        }
        if (str_contains($u, 'laguna')) {
            return $catalogo['ambiente laguna'];
        }
        if (str_contains($u, 'pessoas')) {
            return $catalogo['pessoas'];
        }
        if (str_contains($u, 'servidor')) {
            return $catalogo['servidores'];
        }
        if (str_contains($u, 'educar_index') || str_contains($u, 'escola')) {
            return $catalogo['escola'];
        }

        return 'Acesso liberado ao seu perfil.';
    }

    private function primeiraUrl(Menu $menu): ?string
    {
        if ($menu->isLink() && is_string($menu->link) && $menu->link !== '') {
            return $menu->link;
        }

        foreach ($menu->children ?? [] as $filho) {
            $url = $this->primeiraUrl($filho);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    private function absolutizar(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }


    /**
     * Agrupa módulos na lógica de uso do dia (não alfabética / não aleatória).
     *
     * @param  list<array{titulo:string,descricao:?string,url:string,icone:string,externo:bool,jornada?:string,ordem?:int,cor?:string,acao?:string}>  $atalhos
     * @return list<array{id:string,nome:string,lead:string,icone:string,passo:int,itens:list<array<string,mixed>>}>
     */
    private function agruparModulosJornada(array $atalhos): array
    {
        $meta = [
            'estudante' => [
                'nome' => 'Acompanhar o estudante',
                'lead' => 'Frequência, acolhimento, busca ativa e circulação escolar.',
                'icone' => 'fa-heart',
                'passo' => 1,
            ],
            'escola' => [
                'nome' => 'Operar a unidade',
                'lead' => 'Escolas, pessoas, servidores e grade — o dia a dia da secretaria escolar.',
                'icone' => 'fa-university',
                'passo' => 2,
            ],
            'servicos' => [
                'nome' => 'Serviços da rede',
                'lead' => 'Transporte, merenda, estoque e logística que sustentam a escola.',
                'icone' => 'fa-truck',
                'passo' => 3,
            ],
            'sme' => [
                'nome' => 'Secretaria e apps',
                'lead' => 'Chamados, formação, apps família/professor e contratações.',
                'icone' => 'fa-building',
                'passo' => 4,
            ],
            'sistema' => [
                'nome' => 'Configurar e auditar',
                'lead' => 'Parâmetros, Censo, exportações e governança do ambiente.',
                'icone' => 'fa-sliders',
                'passo' => 5,
            ],
        ];

        $buckets = [];
        foreach ($atalhos as $a) {
            $j = $a['jornada'] ?? 'sistema';
            if (! isset($meta[$j])) {
                $j = 'sistema';
            }
            $buckets[$j][] = $a;
        }

        $out = [];
        foreach ($meta as $id => $info) {
            if (empty($buckets[$id])) {
                continue;
            }
            usort($buckets[$id], static fn (array $x, array $y): int => ($x['ordem'] ?? 99) <=> ($y['ordem'] ?? 99));
            $out[] = [
                'id' => $id,
                'nome' => $info['nome'],
                'lead' => $info['lead'],
                'icone' => $info['icone'],
                'passo' => $info['passo'],
                'itens' => $buckets[$id],
            ];
        }

        return $out;
    }

    /**
     * @return array{jornada:string,ordem:int,cor:string,icone:string,acao:string}
     */
    private function metaJornadaModulo(string $titulo, string $url): array
    {
        $t = mb_strtolower($titulo);
        $u = mb_strtolower($url);

        $hit = static function (string $t, string $u, array $needlesTitulo = [], array $needlesUrl = []): bool {
            foreach ($needlesTitulo as $n) {
                if ($n !== '' && str_contains($t, $n)) {
                    return true;
                }
            }
            foreach ($needlesUrl as $n) {
                if ($n !== '' && str_contains($u, $n)) {
                    return true;
                }
            }

            return false;
        };

        $regras = [
            ['jornada' => 'escola', 'ordem' => 10, 'cor' => '#334155', 'icone' => 'fa-users', 'acao' => 'Abrir cadastros', 'tt' => ['pessoa'], 'uu' => ['pessoas']],
            ['jornada' => 'estudante', 'ordem' => 20, 'cor' => '#0e7490', 'icone' => 'fa-book', 'acao' => 'Abrir diário', 'tt' => ['i-diário', 'i-diario'], 'uu' => ['diario']],
            ['jornada' => 'escola', 'ordem' => 40, 'cor' => '#334155', 'icone' => 'fa-id-badge', 'acao' => 'Ver servidores', 'tt' => ['servidor'], 'uu' => ['servidor']],
            ['jornada' => 'escola', 'ordem' => 50, 'cor' => '#0f5c4c', 'icone' => 'fa-clock-o', 'acao' => 'Abrir grade', 'tt' => ['horário', 'horario', 'quadro'], 'uu' => ['horario']],
            ['jornada' => 'estudante', 'ordem' => 60, 'cor' => '#9a3412', 'icone' => 'fa-search', 'acao' => 'Ver alertas', 'tt' => ['busca ativa'], 'uu' => ['busca-ativa']],
            ['jornada' => 'estudante', 'ordem' => 70, 'cor' => '#0e7490', 'icone' => 'fa-handshake-o', 'acao' => 'Atender', 'tt' => ['atendimento'], 'uu' => ['atendimento']],
            ['jornada' => 'estudante', 'ordem' => 125, 'cor' => '#1e4d7b', 'icone' => 'fa-book', 'acao' => 'Abrir acervo', 'tt' => ['biblioteca'], 'uu' => ['biblioteca']],
            ['jornada' => 'escola', 'ordem' => 30, 'cor' => '#1d4ed8', 'icone' => 'fa-graduation-cap', 'acao' => 'Ir à escola', 'tt' => ['escola'], 'uu' => ['educar_index', 'educar_escola']],
            ['jornada' => 'escola', 'ordem' => 35, 'cor' => '#1e40af', 'icone' => 'fa-share-square-o', 'acao' => 'Ver vagas', 'tt' => ['vagas', 'pré-matrícula', 'pre-matricula'], 'uu' => ['pre-matricula-digital', 'pre-matricula', '/vagas']],
            ['jornada' => 'servicos', 'ordem' => 90, 'cor' => '#0369a1', 'icone' => 'fa-bus', 'acao' => 'Abrir rotas', 'tt' => ['transporte'], 'uu' => ['transporte']],
            ['jornada' => 'servicos', 'ordem' => 100, 'cor' => '#b45309', 'icone' => 'fa-cutlery', 'acao' => 'Ver cardápio', 'tt' => ['aliment'], 'uu' => ['alimentacao']],
            ['jornada' => 'servicos', 'ordem' => 110, 'cor' => '#3f5b6e', 'icone' => 'fa-archive', 'acao' => 'Abrir estoque', 'tt' => ['almox'], 'uu' => ['almoxarifado']],
            ['jornada' => 'sme', 'ordem' => 120, 'cor' => '#b86e00', 'icone' => 'fa-ticket', 'acao' => 'Ver chamados', 'tt' => ['solicit', 'chamado'], 'uu' => ['solicitacao']],
            ['jornada' => 'sme', 'ordem' => 130, 'cor' => '#334155', 'icone' => 'fa-mobile', 'acao' => 'Configurar apps', 'tt' => ['apps'], 'uu' => ['apps-api']],
            ['jornada' => 'sme', 'ordem' => 140, 'cor' => '#0f766e', 'icone' => 'fa-calendar', 'acao' => 'Abrir agenda', 'tt' => ['evento', 'forma'], 'uu' => ['eventos']],
            ['jornada' => 'sme', 'ordem' => 150, 'cor' => '#475569', 'icone' => 'fa-file-text-o', 'acao' => 'Abrir ACT', 'tt' => ['contrata'], 'uu' => ['act', 'contrat']],
            ['jornada' => 'sistema', 'ordem' => 160, 'cor' => '#0f766e', 'icone' => 'fa-share-square-o', 'acao' => 'Exportar', 'tt' => ['export'], 'uu' => ['exportacao']],
            ['jornada' => 'sistema', 'ordem' => 170, 'cor' => '#1d4ed8', 'icone' => 'fa-bar-chart', 'acao' => 'Abrir censo', 'tt' => ['educacenso', 'censo'], 'uu' => ['educacenso']],
            ['jornada' => 'sistema', 'ordem' => 180, 'cor' => '#0e2a3a', 'icone' => 'fa-shield', 'acao' => 'Auditar', 'tt' => ['laguna'], 'uu' => ['laguna']],
            ['jornada' => 'sistema', 'ordem' => 191, 'cor' => '#7f1d1d', 'icone' => 'fa-shield', 'acao' => 'Abrir segurança', 'tt' => ['segurança', 'seguranca'], 'uu' => ['seguranca']],
            ['jornada' => 'sistema', 'ordem' => 190, 'cor' => '#475569', 'icone' => 'fa-cogs', 'acao' => 'Abrir config', 'tt' => ['configura'], 'uu' => ['configur']],
        ];

        // Ordem: regras mais específicas primeiro (já listadas). Escola genérica depois de pessoa/servidor.
        foreach ($regras as $r) {
            if ($hit($t, $u, $r['tt'], $r['uu'])) {
                // Evita "escola" engolir "biblioteca escolar" / "transporte escolar" já tratados
                if (($r['acao'] ?? '') === 'Ir à escola') {
                    if (str_contains($t, 'biblioteca') || str_contains($t, 'transporte') || str_contains($u, 'transporte') || str_contains($u, 'biblioteca')) {
                        continue;
                    }
                }

                return [
                    'jornada' => $r['jornada'],
                    'ordem' => $r['ordem'],
                    'cor' => $r['cor'],
                    'icone' => $r['icone'],
                    'acao' => $r['acao'],
                ];
            }
        }

        return [
            'jornada' => 'sistema',
            'ordem' => 200,
            'cor' => '#1a5f78',
            'icone' => 'fa-th-large',
            'acao' => 'Abrir',
        ];
    }

    /**
     * @param  list<array{titulo:string,descricao:?string,url:string,icone:string,externo:bool}>  $atalhos
     * @return list<array{titulo:string,descricao:?string,url:string,icone:string,externo:bool}>
     */
    private function unicosPorUrl(array $atalhos): array
    {
        $vistos = [];
        $out = [];
        foreach ($atalhos as $a) {
            $chave = rtrim($a['url'], '/');
            if (isset($vistos[$chave])) {
                continue;
            }
            $vistos[$chave] = true;
            $out[] = $a;
        }

        return $out;
    }

    /**
     * @param  list<array<string,mixed>>  $atalhos
     * @return list<array<string,mixed>>
     */
    private function anexarStatusModulos(array $atalhos): array
    {
        foreach ($atalhos as &$a) {
            $id = $a['modulo_id'] ?? $this->resolverModuloIdPorUrl((string) ($a['url'] ?? ''));
            $a['modulo_id'] = $id;
            if ($id === null) {
                $a['modulo_ativo'] = true;
                continue;
            }
            $a['modulo_ativo'] = class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::class)
                ? \iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::ativo($id)
                : true;
        }
        unset($a);

        return $atalhos;
    }

    /**
     * @param  list<array<string,mixed>>  $atalhos
     * @return list<array<string,mixed>>
     */
    private function marcarAcessoPermitido(User $user, array $atalhos): array
    {
        foreach ($atalhos as &$a) {
            if (array_key_exists('acesso_permitido', $a) && $a['acesso_permitido'] === false) {
                continue;
            }
            $id = (string) ($a['modulo_id'] ?? '');
            if ($id === '' || $user->isAdmin()) {
                $a['acesso_permitido'] = true;
                continue;
            }
            if (! class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::class)) {
                $a['acesso_permitido'] = true;
                continue;
            }
            $a['acesso_permitido'] = \iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::usuarioPodeAcessarModulo($user, $id);
        }
        unset($a);

        return $atalhos;
    }

    /**
     * Catálogo completo no painel: com permissão (ativo ou desligado SME) ou
     * sem permissão (cinza no fim) — nunca oculta o card.
     *
     * @param  list<array<string,mixed>>  $atalhos
     * @return list<array<string,mixed>>
     */
    private function incluirModulosCatalogoNoPainel(User $user, array $atalhos): array
    {
        if (! class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\CatalogoModulos::class)) {
            return $atalhos;
        }

        $presentes = [];
        foreach ($atalhos as $a) {
            if (! empty($a['modulo_id'])) {
                $presentes[(string) $a['modulo_id']] = true;
            }
        }

        foreach (\iEducar\Packages\BuritiLagunaAmbiente\Support\CatalogoModulos::todos() as $mod) {
            $id = (string) ($mod['id'] ?? '');
            if ($id === '' || isset($presentes[$id])) {
                continue;
            }
            if (in_array($id, ['laguna-ambiente', 'modulos-config', 'escolar'], true)) {
                continue;
            }

            $url = (string) ($mod['admin_url'] ?? ($mod['rotas'][0] ?? ''));
            if ($url === '' || $this->ocultarModuloPainel((string) $mod['nome'], $url)) {
                continue;
            }

            $temPerm = $this->usuarioTemPermissaoModulo($user, $mod);
            $ativoSme = ! class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::class)
                || \iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::ativo($id);

            $descricao = ! $temPerm
                ? 'Seu perfil não inclui este módulo.'
                : (! $ativoSme
                    ? 'Módulo desativado nas configurações municipais.'
                    : null);

            $atalho = $this->montarAtalhoModulo(
                (string) $mod['nome'],
                $url,
                $descricao,
                isset($mod['icone']) ? (string) $mod['icone'] : null
            );
            $atalho['modulo_id'] = $id;
            $atalho['modulo_ativo'] = $ativoSme;
            $atalho['acesso_permitido'] = $temPerm;
            $atalhos[] = $atalho;
        }

        return $this->unicosPorUrl($atalhos);
    }

    /**
     * Núcleo i-Educar (Configurações, Escola, …) permanece visível sem ACL —
     * card cinza no fim, sem clique.
     *
     * @param  list<array<string,mixed>>  $atalhos
     * @return list<array<string,mixed>>
     */
    private function incluirNucleoPainelSemAcesso(User $user, array $atalhos): array
    {
        if ($user->isAdmin()) {
            return $atalhos;
        }

        $nucleo = [
            [
                'titulo' => 'Configurações',
                'url' => '/intranet/educar_configuracoes_index.php',
                'processos' => [25, 999873],
                'icone' => 'fa-cog',
            ],
            [
                'titulo' => 'Escola',
                'url' => '/intranet/educar_index.php',
                'processos' => [55],
                'icone' => 'fa-graduation-cap',
            ],
            [
                'titulo' => 'Pessoas',
                'url' => '/intranet/educar_pessoas_index.php',
                'processos' => [7],
                'icone' => 'fa-users',
            ],
            [
                'titulo' => 'Servidores',
                'url' => '/rh-educacional',
                'processos' => [71],
                'icone' => 'fa-id-badge',
            ],
            [
                'titulo' => 'Ambiente Laguna',
                'url' => '/laguna-ambiente',
                'processos' => [889900],
                'icone' => 'fa-shield',
            ],
        ];

        foreach ($nucleo as $item) {
            $temPerm = false;
            foreach ($item['processos'] as $processo) {
                try {
                    if ($user->can('view', $processo)) {
                        $temPerm = true;
                        break;
                    }
                } catch (\Throwable) {
                }
            }
            if ($temPerm) {
                continue;
            }

            $atalho = $this->montarAtalhoModulo(
                $item['titulo'],
                $item['url'],
                'Seu perfil não inclui este módulo.',
                $item['icone']
            );
            $atalho['acesso_permitido'] = false;
            $atalho['modulo_ativo'] = true;
            $atalhos[] = $atalho;
        }

        return $this->unicosPorUrl($atalhos);
    }

    /**
     * @param  array{processos?:list<int>,id?:string}  $mod
     */
    private function usuarioTemPermissaoModulo(User $user, array $mod): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $id = (string) ($mod['id'] ?? '');
        if ($id !== '' && class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::usuarioPodeAcessarModulo($user, $id);
        }

        $processos = array_values(array_filter(
            array_map('intval', $mod['processos'] ?? []),
            static fn (int $p): bool => $p > 0
        ));
        if ($processos === []) {
            return false;
        }

        try {
            foreach ($processos as $processo) {
                if ($user->can('view', $processo)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function resolverModuloIdPorUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $url;
        }
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\InformesModulo::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\InformesModulo::idDaRequisicao($path);
        }

        return null;
    }
}

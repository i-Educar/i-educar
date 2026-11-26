<?php

namespace App\Support;

use App\Menu;
use App\Services\MenuCacheService;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * CUSTOMIZAÇÕES BURITI — não faz parte do upstream Portabilis.
 *
 * Painel de boas-vindas nos índices legados (*_index.php): visão geral do módulo
 * e dos grupos do submenu — sem listar todas as telas (isso permanece no submenu).
 */
final class LegacyModuleIndexPanel
{
    public const CUSTOM_ID = 'customizacoes-buriti-module-index';

    public const CUSTOM_LABEL = 'CUSTOMIZAÇÕES BURITI';

    /** Máximo de grupos de 1º nível (já são poucos; evita painel infinito em menus anômalos). */
    private const MAX_GRUPOS = 12;

    /**
     * @param  array{descricao?:string,icone?:string}  $extra
     */
    public static function render(string $titulo, int|string $processoAp, array $extra = []): string
    {
        try {
            $dados = self::dados($titulo, $processoAp, $extra);

            return View::make('legacy.module-index-panel', $dados)->render();
        } catch (Throwable $e) {
            return '<!-- '.self::CUSTOM_ID.' erro: '.e($e->getMessage()).' -->'
                .'<div class="svc-module-index svc-module-index--fallback" data-customizacao="'.e(self::CUSTOM_ID).'">'
                .'<p class="svc-module-index__badge">'.e(self::CUSTOM_LABEL).'</p>'
                .'<h2 class="svc-module-index__titulo">'.e($titulo).'</h2>'
                .'<p class="svc-module-index__lead">Use o submenu acima para navegar neste módulo.</p>'
                .'</div>';
        }
    }

    /**
     * @param  array{descricao?:string,icone?:string}  $extra
     * @return array{
     *   customId:string,
     *   customLabel:string,
     *   titulo:string,
     *   descricao:string,
     *   icone:string,
     *   processo:int|string,
     *   perfil:string,
     *   grupos:list<array{titulo:string,descricao:string,qtd:int,exemplos:list<string>,url:?string}>,
     *   totalGrupos:int,
     *   totalAcoes:int
     * }
     */
    public static function dados(string $titulo, int|string $processoAp, array $extra = []): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $perfil = $user?->type?->nm_tipo
            ?? $user?->name
            ?? 'usuário autenticado';

        [$grupos, $totalAcoes] = self::gruposChave($user, $processoAp);

        $descricao = trim((string) ($extra['descricao'] ?? ''));
        if ($descricao === '') {
            $descricao = self::descricaoPadrao($titulo);
        }

        return [
            'customId' => self::CUSTOM_ID,
            'customLabel' => self::CUSTOM_LABEL,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'icone' => (string) ($extra['icone'] ?? 'fa-th-large'),
            'processo' => $processoAp,
            'perfil' => (string) $perfil,
            'grupos' => $grupos,
            'totalGrupos' => count($grupos),
            'totalAcoes' => $totalAcoes,
        ];
    }

    /**
     * @return array{0:list<array{titulo:string,descricao:string,qtd:int,exemplos:list<string>,url:?string}>,1:int}
     */
    private static function gruposChave(?User $user, int|string $processoAp): array
    {
        if ($user === null) {
            return [[], 0];
        }

        $processMenu = app(MenuCacheService::class)->getProcessMenuData($processoAp);
        if ($processMenu === false) {
            return [[], 0];
        }

        $rootId = (int) ($processMenu['root_id'] ?? 0);
        if ($rootId < 1) {
            return [[], 0];
        }

        /** @var Collection<int, Menu> $tree */
        $tree = collect(app(MenuCacheService::class)->getMenuByUser($user));
        $root = self::encontrarNo($tree, $rootId)
            ?? Menu::query()->with('children.children.children')->find($rootId);

        if ($root === null) {
            return [[], 0];
        }

        $filhos = $root->children instanceof Collection
            ? $root->children->sortBy('order')
            : collect($root->children ?? [])->sortBy('order');

        $grupos = [];
        $totalAcoes = 0;

        foreach ($filhos as $filho) {
            if (! $filho instanceof Menu) {
                continue;
            }
            if (method_exists($filho, 'hasLink') && ! $filho->hasLink()) {
                continue;
            }

            $links = self::coletarLinks($filho);
            $qtd = count($links);
            if ($qtd === 0 && ! ($filho->isLink() && self::podeVer($filho))) {
                continue;
            }

            if ($qtd === 0 && $filho->isLink()) {
                $qtd = 1;
                $links = [[
                    'titulo' => (string) $filho->title,
                    'url' => (string) $filho->link,
                ]];
            }

            $totalAcoes += $qtd;
            $exemplos = array_values(array_map(
                static fn (array $l): string => $l['titulo'],
                array_slice($links, 0, 3)
            ));

            $descMenu = trim((string) ($filho->description ?? ''));
            $descricao = $descMenu !== ''
                ? $descMenu
                : self::descricaoGrupo((string) $filho->title, $qtd, $exemplos);

            // Só linka o card se o próprio item do submenu for um link direto (sem filhos).
            $url = null;
            if ($filho->isLink() && ! $filho->hasLinkInSubmenu() && self::podeVer($filho)) {
                $url = (string) $filho->link;
            }

            $grupos[] = [
                'titulo' => (string) $filho->title,
                'descricao' => $descricao,
                'qtd' => $qtd,
                'exemplos' => $exemplos,
                'url' => $url,
            ];
        }

        if (count($grupos) > self::MAX_GRUPOS) {
            $grupos = array_slice($grupos, 0, self::MAX_GRUPOS);
        }

        return [$grupos, $totalAcoes];
    }

    /**
     * @param  Collection<int, Menu>|iterable<Menu>  $nos
     */
    private static function encontrarNo(iterable $nos, int $id): ?Menu
    {
        foreach ($nos as $no) {
            if (! $no instanceof Menu) {
                continue;
            }
            if ((int) $no->getKey() === $id) {
                return $no;
            }
            $achou = self::encontrarNo($no->children ?? [], $id);
            if ($achou !== null) {
                return $achou;
            }
        }

        return null;
    }

    /**
     * @return list<array{titulo:string,url:string}>
     */
    private static function coletarLinks(Menu $menu): array
    {
        $itens = [];

        if ($menu->isLink() && self::podeVer($menu)) {
            $itens[] = [
                'titulo' => (string) $menu->title,
                'url' => (string) $menu->link,
            ];
        }

        $filhos = $menu->children instanceof Collection
            ? $menu->children->sortBy('order')
            : collect($menu->children ?? [])->sortBy('order');

        foreach ($filhos as $filho) {
            if (! $filho instanceof Menu) {
                continue;
            }
            if (method_exists($filho, 'hasLink') && ! $filho->hasLink()) {
                continue;
            }
            foreach (self::coletarLinks($filho) as $item) {
                $itens[] = $item;
            }
        }

        return $itens;
    }

    private static function podeVer(Menu $menu): bool
    {
        if (empty($menu->link)) {
            return false;
        }

        $process = $menu->process;
        if ($process === null || $process === '' || (int) $process === 0) {
            return true;
        }

        try {
            return Gate::allows('view', $process);
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * @param  list<string>  $exemplos
     */
    private static function descricaoGrupo(string $titulo, int $qtd, array $exemplos): string
    {
        $amostra = $exemplos !== []
            ? ' Inclui '.implode(', ', array_slice($exemplos, 0, 2))
                .(count($exemplos) > 2 ? '…' : '').'.'
            : '';

        if ($qtd <= 1) {
            return "Área «{$titulo}» do submenu.{$amostra}";
        }

        return "Grupo do submenu com {$qtd} ações.{$amostra}";
    }

    private static function descricaoPadrao(string $titulo): string
    {
        return "Visão geral do módulo «{$titulo}». O submenu acima concentra as ações; "
            .'aqui só os grupos principais para orientar o caminho.';
    }
}

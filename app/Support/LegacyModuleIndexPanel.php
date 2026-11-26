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
 * CUSTOMIZAÇÃO Serventec / Laguna (PE-77) — não faz parte do upstream Portabilis.
 *
 * Preenche a área vazia das telas `*_index.php` do legado (após skins) com um
 * painel minimalista: resumo do módulo + atalhos permitidos ao perfil.
 */
final class LegacyModuleIndexPanel
{
    public const CUSTOM_ID = 'serventec-laguna-module-index';

    public const CUSTOM_LABEL = 'Customização Serventec · Laguna';

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
     *   grupos:list<array{titulo:string,itens:list<array{titulo:string,url:string,descricao:?string}}>>,
     *   total:int
     * }
     */
    public static function dados(string $titulo, int|string $processoAp, array $extra = []): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $perfil = $user?->type?->nm_tipo
            ?? $user?->name
            ?? 'usuário autenticado';

        $grupos = self::gruposPermitidos($user, $processoAp);
        $total = 0;
        foreach ($grupos as $g) {
            $total += count($g['itens']);
        }

        $descricao = trim((string) ($extra['descricao'] ?? ''));
        if ($descricao === '') {
            $descricao = self::descricaoPadrao($titulo, $total);
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
            'total' => $total,
        ];
    }

    /**
     * @return list<array{titulo:string,itens:list<array{titulo:string,url:string,descricao:?string}}>>
     */
    private static function gruposPermitidos(?User $user, int|string $processoAp): array
    {
        if ($user === null) {
            return [];
        }

        $processMenu = app(MenuCacheService::class)->getProcessMenuData($processoAp);
        if ($processMenu === false) {
            return [];
        }

        $rootId = (int) ($processMenu['root_id'] ?? 0);
        if ($rootId < 1) {
            return [];
        }

        /** @var Collection<int, Menu> $tree */
        $tree = collect(app(MenuCacheService::class)->getMenuByUser($user));
        $root = self::encontrarNo($tree, $rootId);
        if ($root === null) {
            // Fallback: menu ainda não filtrado (ex.: cache) — carrega filhos ativos e aplica Gate.
            $root = Menu::query()->with('children.children.children')->find($rootId);
            if ($root === null) {
                return [];
            }
        }

        $grupos = [];
        $filhos = $root->children instanceof Collection
            ? $root->children->sortBy('order')
            : collect($root->children ?? [])->sortBy('order');

        foreach ($filhos as $filho) {
            if (! $filho instanceof Menu) {
                continue;
            }
            if (method_exists($filho, 'hasLink') && ! $filho->hasLink()) {
                continue;
            }

            $itens = self::coletarLinks($filho, $filho->title);
            if ($itens === []) {
                continue;
            }

            $grupos[] = [
                'titulo' => (string) $filho->title,
                'itens' => $itens,
            ];
        }

        // Se o root for link direto sem filhos úteis, lista o próprio.
        if ($grupos === [] && $root->isLink() && self::podeVer($root)) {
            $grupos[] = [
                'titulo' => 'Opções',
                'itens' => [[
                    'titulo' => (string) $root->title,
                    'url' => (string) $root->link,
                    'descricao' => $root->description ? (string) $root->description : null,
                ]],
            ];
        }

        return $grupos;
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
            $filhos = $no->children ?? [];
            $achou = self::encontrarNo($filhos, $id);
            if ($achou !== null) {
                return $achou;
            }
        }

        return null;
    }

    /**
     * @return list<array{titulo:string,url:string,descricao:?string}>
     */
    private static function coletarLinks(Menu $menu, string $grupo): array
    {
        $itens = [];

        if ($menu->isLink() && self::podeVer($menu)) {
            $itens[] = [
                'titulo' => (string) $menu->title,
                'url' => (string) $menu->link,
                'descricao' => self::caminhoRelativo($menu, $grupo),
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
            foreach (self::coletarLinks($filho, $grupo) as $item) {
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

    private static function caminhoRelativo(Menu $menu, string $grupo): ?string
    {
        $desc = trim((string) ($menu->description ?? ''));
        if ($desc !== '') {
            return $desc;
        }

        if ((string) $menu->title === $grupo) {
            return null;
        }

        return $grupo.' › '.$menu->title;
    }

    private static function descricaoPadrao(string $titulo, int $total): string
    {
        if ($total > 0) {
            return "Módulo «{$titulo}» — {$total} opção(ões) disponíveis para o seu perfil. Escolha um atalho ou use o submenu.";
        }

        return "Módulo «{$titulo}» — nenhuma opção liberada para o seu perfil neste momento. Verifique permissões em Tipos de usuário.";
    }
}

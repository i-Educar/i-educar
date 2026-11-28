<?php

namespace App\Services;

use App\Menu;
use App\User;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MenuCacheService
{
    private const TTL_MENU_TREE = 86400;

    private const TTL_PROCESS_MENU = 604800;

    private ConfigContract $config;

    private CacheContract $cache;

    public function __construct(ConfigContract $config, CacheContract $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * @return array|mixed|null
     */
    public function getMenuByUser(User $user)
    {
        $key = $this->getUserKey($user);
        $client = $this->getClient();

        $cacheMenus = $this->remember($key, ['menus', $client, $key], self::TTL_MENU_TREE, function () use ($user) {
            return Menu::user($user);
        });

        return $cacheMenus;
    }

    public function putMenuCache(Collection $adminMenus, User $user)
    {
        $key = $this->getUserKey($user);
        $client = $this->getClient();

        $this->put($key, ['menus', $client, $key], $adminMenus, self::TTL_MENU_TREE);
    }

    /**
     * @return array{attributes: array, root_id: int, ancestor_ids: array<int>}|false
     */
    public function getProcessMenuData(int|string|null $process): array|false
    {
        if (empty($process)) {
            return false;
        }

        $client = $this->getClient();
        $key = 'menu-process-'.$client.'-'.$process;

        return $this->remember($key, ['menus', $client], self::TTL_PROCESS_MENU, function () use ($process) {
            $menu = Menu::query()->where('process', $process)->first();

            if ($menu === null) {
                return false;
            }

            $ancestors = $menu->ancestors()->get();

            return [
                'attributes' => $menu->getAttributes(),
                'root_id' => $ancestors->whereNull('parent_id')->first()?->getKey() ?? $menu->getKey(),
                'ancestor_ids' => $ancestors->pluck('id')->all(),
            ];
        });
    }

    public function flushAll(): void
    {
        $client = (string) DB::connection()->getConfig('database');

        if ($this->supportsTags()) {
            $this->cache->tags($client)->flush();
            $this->cache->tags(['menus', $client])->flush();

            return;
        }

        // Store sem tags (ex.: file/database): limpa chaves conhecidas do menu.
        $this->cache->forget('menu-process-'.$client);
    }

    public function flushMenuTag($tagMenu)
    {
        $client = $this->getClient();
        $tag = 'menu-'.$client.'-'.$tagMenu;

        if ($this->supportsTags()) {
            $this->cache->tags($tag)->flush();

            return;
        }

        $this->cache->forget($tag);
    }

    private function supportsTags(): bool
    {
        return method_exists($this->cache, 'supportsTags') && $this->cache->supportsTags();
    }

    /**
     * @param  list<string>  $tags
     * @param  callable(): mixed  $callback
     */
    private function remember(string $key, array $tags, int $ttl, callable $callback): mixed
    {
        if ($this->supportsTags()) {
            return $this->cache->tags($tags)->remember($key, $ttl, $callback);
        }

        return $this->cache->remember($this->untaggedKey($key, $tags), $ttl, $callback);
    }

    /**
     * @param  list<string>  $tags
     */
    private function put(string $key, array $tags, mixed $value, int $ttl): void
    {
        if ($this->supportsTags()) {
            $this->cache->tags($tags)->put($key, $value, $ttl);

            return;
        }

        $this->cache->put($this->untaggedKey($key, $tags), $value, $ttl);
    }

    /**
     * @param  list<string>  $tags
     */
    private function untaggedKey(string $key, array $tags): string
    {
        return 'menu-fallback:'.implode('.', $tags).':'.$key;
    }

    private function getClient(): string
    {
        return $this->config->get('legacy.app.database.dbname');
    }

    private function getUserKey(User $user): string
    {
        $tipo = (string) ($user->type->cod_tipo_usuario ?? $user->ref_cod_tipo_usuario ?? '0');
        $fp = '';
        if (class_exists(\iEducar\Packages\BuritiPapeisTemporarios\Services\PapeisTemporarios::class)) {
            try {
                $fp = '-'.\iEducar\Packages\BuritiPapeisTemporarios\Services\PapeisTemporarios::fingerprint($user);
            } catch (\Throwable) {
                $fp = '';
            }
        }

        return 'menu-'.$this->getClient().'-'.$tipo.$fp;
    }
}

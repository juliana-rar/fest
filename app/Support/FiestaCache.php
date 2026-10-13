<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for festival queries.
 *
 * Every cached key embeds the current "version". Editing a festival,
 * municipality or comarca from the admin bumps the version, which
 * invalidates all derived entries at once on any cache store (no tags needed).
 */
class FiestaCache
{
    private const VERSION_KEY = 'fiestas.cache-version';

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function remember(string $name, array $params, int $seconds, Closure $callback): mixed
    {
        ksort($params);
        $key = sprintf('fiestas.v%d.%s.%s', self::version(), $name, md5(json_encode($params)));

        return Cache::remember($key, $seconds, $callback);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value site settings. Values are stored JSON-encoded so arrays
 * (social links, story templates…) round-trip transparently.
 * The whole table is cached forever and flushed on every write.
 */
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const STORY_TEMPLATES = ['nit', 'senyera', 'minimal'];

    public const SOCIAL_NETWORKS = ['instagram', 'tiktok', 'x', 'facebook', 'youtube'];

    public const DEFAULTS = [
        'hero_title_ca' => 'Totes les festes majors de Catalunya',
        'hero_title_es' => 'Todas las fiestas mayores de Cataluña',
        'hero_subtitle_ca' => 'Castells, correfocs, gegants i focs artificials: troba la propera festa a prop teu.',
        'hero_subtitle_es' => 'Castells, correfocs, gegants y fuegos artificiales: encuentra la próxima fiesta cerca de ti.',
        'logo' => null,
        'color_primary' => '#E63946',
        'color_secondary' => '#FFB703',
        'color_background' => '#0F1020',
        'social' => [
            'instagram' => 'https://www.instagram.com/',
            'tiktok' => '',
            'x' => '',
            'facebook' => '',
            'youtube' => '',
        ],
        'seo_title' => 'Festes Majors de Catalunya — calendari i mapa',
        'seo_description' => 'Calendari i mapa interactiu de les festes majors dels municipis de Catalunya. Troba les festes més properes i comparteix-les a Instagram.',
        'og_image' => null,
        'short_url' => 'festesmajors.cat',
        'story_default' => 'nit',
        'story_templates' => [
            'nit' => ['name' => 'Nit de festa', 'enabled' => true, 'accent' => '#5EEAD4'],
            'senyera' => ['name' => 'Senyera', 'enabled' => true, 'accent' => '#E63946'],
            'minimal' => ['name' => 'Minimal', 'enabled' => true, 'accent' => '#FFB703'],
        ],
    ];

    /** @return array<string, mixed> */
    public static function allValues(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return static::query()->pluck('value', 'key')
                ->map(fn ($v) => json_decode($v, true))
                ->all();
        });

        return array_replace(self::DEFAULTS, $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allValues()[$key] ?? $default;
    }

    /** @param array<string, mixed> $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** Localised setting, e.g. localized('hero_title') → hero_title_ca / hero_title_es. */
    public static function localized(string $key): ?string
    {
        return self::get($key.'_'.app()->getLocale()) ?: self::get($key.'_ca');
    }

    /**
     * All story templates (enabled or not) merged with defaults.
     *
     * @return array<string, array{name: string, enabled: bool, accent: string}>
     */
    public static function allStoryTemplates(): array
    {
        $stored = (array) self::get('story_templates', []);
        $templates = [];

        foreach (self::STORY_TEMPLATES as $key) {
            $templates[$key] = array_replace(self::DEFAULTS['story_templates'][$key], (array) ($stored[$key] ?? []));
        }

        return $templates;
    }

    /** Enabled story templates only (never empty). */
    public static function storyTemplates(): array
    {
        $enabled = array_filter(self::allStoryTemplates(), fn ($t) => ! empty($t['enabled']));

        return $enabled ?: ['nit' => self::DEFAULTS['story_templates']['nit']];
    }

    public static function defaultStoryTemplate(): string
    {
        $templates = self::storyTemplates();
        $default = self::get('story_default', 'nit');

        return isset($templates[$default]) ? $default : array_key_first($templates);
    }
}

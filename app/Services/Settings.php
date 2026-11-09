<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\QatarPhone;
use Illuminate\Contracts\Cache\Repository as Cache;

class Settings
{
    private const CACHE_KEY = 'shop.settings';

    /**
     * The settings resolved during this request.
     *
     * @var array<string, mixed>|null
     */
    private ?array $values = null;

    public function __construct(private readonly Cache $cache) {}

    /**
     * Get a setting value, falling back to the configured default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get a setting that holds a Qatar phone number.
     */
    public function phone(string $key): ?QatarPhone
    {
        return QatarPhone::tryParse($this->get($key));
    }

    /**
     * Get every setting, with saved values layered over the configured defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values ??= array_merge(
            config('shop.settings'),
            $this->cache->rememberForever(
                self::CACHE_KEY,
                fn (): array => Setting::query()->pluck('value', 'key')->all(),
            ),
        );
    }

    /**
     * Save the given settings and clear the cached copy.
     *
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->cache->forget(self::CACHE_KEY);
        $this->values = null;
    }
}

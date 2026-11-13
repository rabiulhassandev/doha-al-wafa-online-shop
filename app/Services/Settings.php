<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\QatarPhone;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

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
     * Get a setting saved encrypted, such as an ad platform's access token.
     */
    public function secret(string $key): ?string
    {
        $value = $this->get($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    public function feedToken(): ?string
    {
        $value = $this->get('feed_token');

        if ($value === null || $value === '') {
            return null;
        }

        // Existing installations have a plaintext token until the owner saves the settings again.
        return preg_match('/\A[A-Za-z0-9_-]{8,64}\z/', $value) === 1 ? $value : Crypt::decryptString($value);
    }

    /**
     * Encrypt a value for saving as a secret setting.
     */
    public static function encryptSecret(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Get the ISO weekdays with no delivery (1 = Monday … 7 = Sunday).
     *
     * @return list<int>
     */
    public function deliveryDaysOff(): array
    {
        return array_values(array_map('intval', (array) $this->get('delivery_days_off')));
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

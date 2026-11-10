<?php

namespace App\Models;

use Database\Factories\ShortLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A short link such as "/p/k7q2mx" for social bios, ads and influencers. It opens a store page with
 * its campaign tags, and can apply a discount code. Orders remember the link they came from.
 */
#[Fillable(['code', 'label', 'destination', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'coupon_code', 'is_active'])]
class ShortLink extends Model
{
    /** @use HasFactory<ShortLinkFactory> */
    use HasFactory;

    /**
     * Letters and digits that cannot be mistaken for each other when typed from a screen.
     */
    private const CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const CODE_LENGTH = 6;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'clicks' => 0,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Make a code no other link uses.
     */
    public static function generateCode(): string
    {
        do {
            $code = collect(range(1, self::CODE_LENGTH))
                ->map(fn (): string => self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)])
                ->implode('');
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the orders placed after opening the link.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the short address to share.
     */
    public function shortUrl(): string
    {
        return route('short-links.redirect', ['code' => $this->code]);
    }

    /**
     * Get the store address the link opens: the destination with the campaign tags, the discount
     * code and the link's own code ("sl"), which the order remembers.
     */
    public function targetUrl(): string
    {
        $path = Str::before($this->destination, '?');
        parse_str(Str::contains($this->destination, '?') ? Str::after($this->destination, '?') : '', $query);

        $query = [
            ...$query,
            ...array_filter([
                'utm_source' => $this->utm_source,
                'utm_medium' => $this->utm_medium,
                'utm_campaign' => $this->utm_campaign,
                'utm_content' => $this->utm_content,
                'coupon' => $this->coupon_code,
            ], fn (?string $value): bool => filled($value)),
            'sl' => $this->code,
        ];

        return url($path).'?'.http_build_query($query);
    }
}

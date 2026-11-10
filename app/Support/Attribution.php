<?php

namespace App\Support;

use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonException;

/**
 * Where a shopper came from: campaign tags, an ad platform's click ID, the referring site and
 * the first page they landed on. The last visit that came from somewhere is kept for 30 days.
 */
final readonly class Attribution
{
    public const COOKIE = 'attribution';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    /**
     * Ad platforms' click ID parameters, by the platform they belong to.
     */
    private const CLICK_IDS = ['fbclid' => 'meta', 'ttclid' => 'tiktok', 'ScCid' => 'snapchat', 'gclid' => 'google'];

    private const UTM_FIELDS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    /**
     * @param  array<string, string>  $utm  campaign tags keyed by parameter name
     */
    public function __construct(
        public array $utm = [],
        public ?string $clickIdType = null,
        public ?string $clickId = null,
        public ?string $referrer = null,
        public ?string $landingUrl = null,
        public ?int $shortLinkId = null,
    ) {}

    /**
     * Read where this visit came from, or return null for a direct visit or a page within the store.
     */
    public static function fromRequest(Request $request): ?self
    {
        $utm = collect(self::UTM_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => Str::limit(trim((string) $request->query($field)), 100, '')])
            ->filter(fn (string $value): bool => $value !== '')
            ->all();

        $clickIdType = null;
        $clickId = null;

        foreach (self::CLICK_IDS as $parameter => $platform) {
            if (filled($request->query($parameter))) {
                $clickIdType = $platform;
                $clickId = Str::limit((string) $request->query($parameter), 255, '');

                break;
            }
        }

        $shortLinkCode = (string) $request->query('sl');
        $shortLinkId = $shortLinkCode === '' ? null : ShortLink::query()->where('code', $shortLinkCode)->value('id');

        $referrer = (string) $request->headers->get('referer');
        $isExternalReferrer = $referrer !== '' && parse_url($referrer, PHP_URL_HOST) !== $request->getHost();

        if ($utm === [] && $clickId === null && $shortLinkId === null && ! $isExternalReferrer) {
            return null;
        }

        return new self(
            utm: $utm,
            clickIdType: $clickIdType,
            clickId: $clickId,
            referrer: $isExternalReferrer ? Str::limit($referrer, 500, '') : null,
            landingUrl: Str::limit($request->fullUrl(), 500, ''),
            shortLinkId: $shortLinkId,
        );
    }

    /**
     * Read the attribution kept in the shopper's cookie.
     */
    public static function fromCookie(?string $value): ?self
    {
        if (blank($value)) {
            return null;
        }

        try {
            $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        return new self(
            utm: array_intersect_key(array_filter((array) ($data['utm'] ?? []), 'is_string'), array_flip(self::UTM_FIELDS)),
            clickIdType: is_string($data['click_id_type'] ?? null) ? $data['click_id_type'] : null,
            clickId: is_string($data['click_id'] ?? null) ? $data['click_id'] : null,
            referrer: is_string($data['referrer'] ?? null) ? $data['referrer'] : null,
            landingUrl: is_string($data['landing_url'] ?? null) ? $data['landing_url'] : null,
            shortLinkId: is_int($data['short_link_id'] ?? null) ? $data['short_link_id'] : null,
        );
    }

    /**
     * Get the value to keep in the cookie.
     */
    public function toCookieValue(): string
    {
        return json_encode([
            'utm' => $this->utm,
            'click_id_type' => $this->clickIdType,
            'click_id' => $this->clickId,
            'referrer' => $this->referrer,
            'landing_url' => $this->landingUrl,
            'short_link_id' => $this->shortLinkId,
        ]);
    }

    /**
     * Get the order columns this attribution fills.
     *
     * @return array<string, string|int|null>
     */
    public function orderAttributes(): array
    {
        return [
            ...array_fill_keys(self::UTM_FIELDS, null),
            ...$this->utm,
            'click_id_type' => $this->clickIdType,
            'click_id' => $this->clickId,
            'referrer' => $this->referrer,
            'landing_url' => $this->landingUrl,
            'short_link_id' => $this->shortLinkId,
        ];
    }
}

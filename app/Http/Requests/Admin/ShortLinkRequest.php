<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use App\Models\ShortLink;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShortLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept a full store address or a path, store the path; codes and sources in lower case.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'destination' => is_string($this->input('destination')) ? $this->storePath(trim($this->input('destination'))) : $this->input('destination'),
            'code' => is_string($this->input('code')) ? Str::lower(trim($this->input('code'))) : $this->input('code'),
            'utm_source' => is_string($this->input('utm_source')) ? Str::lower(trim($this->input('utm_source'))) : $this->input('utm_source'),
            'utm_medium' => is_string($this->input('utm_medium')) ? Str::lower(trim($this->input('utm_medium'))) : $this->input('utm_medium'),
            'coupon_code' => is_string($this->input('coupon_code')) ? Coupon::normalizeCode($this->input('coupon_code')) : $this->input('coupon_code'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            // A path on this store: "/ar/products/abaya?color=navy". Never "//other.site" or the short links themselves.
            'destination' => ['required', 'string', 'max:500', 'regex:#^/(?![/\\\\])\S*$#', 'not_regex:#^/p(/|$)#'],
            'code' => ['nullable', 'string', 'regex:/^[a-z0-9-]{3,20}$/', Rule::unique('short_links', 'code')->ignore($this->route('short_link'))],
            'utm_source' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'utm_medium' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'utm_content' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', Rule::exists('coupons', 'code')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination.regex' => 'Paste a page address from this store, such as /ar/products/abaya?color=navy.',
            'destination.not_regex' => 'A short link cannot open another short link.',
            'code.regex' => 'Use 3 to 20 lower-case letters, digits or dashes, such as eid-sara.',
            'code.unique' => 'Another short link already uses this code.',
            'utm_source.regex' => 'Use one word, such as instagram or tiktok.',
            'utm_medium.regex' => 'Use one word, such as social, bio or paid.',
            'coupon_code.exists' => 'There is no discount code with this name.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'label' => 'name',
            'destination' => 'page',
            'utm_source' => 'source',
            'utm_medium' => 'medium',
            'utm_campaign' => 'campaign',
            'utm_content' => 'content',
            'coupon_code' => 'discount code',
        ];
    }

    /**
     * Get the validated short link attributes. A blank code keeps the current one, or gets a new one.
     *
     * @return array<string, mixed>
     */
    public function shortLinkAttributes(?ShortLink $shortLink = null): array
    {
        return [
            'label' => $this->validated('label'),
            'destination' => $this->validated('destination'),
            'code' => $this->validated('code') ?: ($shortLink?->code ?? ShortLink::generateCode()),
            'utm_source' => $this->validated('utm_source') ?: null,
            'utm_medium' => $this->validated('utm_medium') ?: null,
            'utm_campaign' => $this->validated('utm_campaign') ?: null,
            'utm_content' => $this->validated('utm_content') ?: null,
            'coupon_code' => $this->validated('coupon_code') ?: null,
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * Turn a pasted address on this store ("https://shop.qa/ar/shop?x=1") into its path ("/ar/shop?x=1").
     * Addresses on other sites are left alone, so validation rejects them.
     */
    private function storePath(string $destination): string
    {
        $parts = parse_url($destination);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return $destination;
        }

        $storeHosts = array_filter([parse_url((string) config('app.url'), PHP_URL_HOST), $this->getHost()]);

        if (! in_array(Str::lower($parts['host']), array_map(Str::lower(...), $storeHosts), true)) {
            return $destination;
        }

        return ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}

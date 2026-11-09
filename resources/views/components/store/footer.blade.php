@inject('settings', 'App\Services\Settings')

@php
    $whatsApp = $settings->phone('whatsapp_number');
    $phone = $settings->phone('contact_phone');
    $email = $settings->get('contact_email');
    $socialLinks = collect([
        'instagram' => ['Instagram', $settings->get('instagram_url')],
        'tiktok' => ['TikTok', $settings->get('tiktok_url')],
        'facebook' => ['Facebook', $settings->get('facebook_url')],
        'snapchat' => ['Snapchat', $settings->get('snapchat_url')],
    ])->filter(fn (array $link): bool => filled($link[1]));
@endphp

<footer class="mt-16 border-t border-sand-200 bg-sand-100">
    <div class="page-container grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-3">
            <x-store.logo />
            <p class="max-w-xs text-sm text-ink-500">
                {{ __('Jelabiyas in many colors, delivered across Qatar. Pay cash on delivery.') }}
            </p>
        </div>

        @if ($whatsApp || $phone || filled($email))
            <div>
                <h2 class="text-sm font-semibold">{{ __('Contact us') }}</h2>
                <ul class="mt-4 space-y-3 text-sm text-ink-700">
                    @if ($whatsApp)
                        <li>
                            <a href="https://wa.me/{{ $whatsApp->whatsAppNumber() }}" class="inline-flex items-center gap-2 hover:text-maroon-700" rel="noopener" target="_blank">
                                <x-icon name="whatsapp" class="size-5" />
                                <span>{{ __('WhatsApp') }}</span>
                                <span dir="ltr">{{ $whatsApp->formatted() }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($phone)
                        <li>
                            <a href="tel:{{ $phone->e164() }}" class="inline-flex items-center gap-2 hover:text-maroon-700">
                                <x-icon name="phone" class="size-5" />
                                <span dir="ltr">{{ $phone->formatted() }}</span>
                            </a>
                        </li>
                    @endif
                    @if (filled($email))
                        <li>
                            <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 hover:text-maroon-700">
                                <x-icon name="mail" class="size-5" />
                                <span dir="ltr">{{ $email }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        @if ($socialLinks->isNotEmpty())
            <div>
                <h2 class="text-sm font-semibold">{{ __('Follow us') }}</h2>
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($socialLinks as $network => [$label, $url])
                        <li>
                            <a href="{{ $url }}" class="inline-flex size-11 items-center justify-center rounded-full border border-sand-300 bg-white hover:border-maroon-700 hover:text-maroon-700" rel="noopener" target="_blank">
                                <x-icon :name="$network" class="size-5" />
                                <span class="sr-only">{{ $label }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <h2 class="text-sm font-semibold">{{ __('Language') }}</h2>
            <x-store.language-switcher class="-ms-3 mt-2" />
        </div>
    </div>

    <div class="border-t border-sand-200">
        <div class="page-container flex flex-col gap-2 py-6 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} <span dir="ltr">{{ $settings->get('store_name') }}</span>. {{ __('All rights reserved.') }}</p>
            <p class="inline-flex items-center gap-2">
                <x-icon name="cash" class="size-4" />
                {{ __('Cash on delivery across Qatar') }}
            </p>
        </div>
    </div>
</footer>

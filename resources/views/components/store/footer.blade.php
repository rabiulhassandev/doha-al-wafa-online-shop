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
    // Pages without a session, such as "page not found", have no error bag.
    $subscribeError = isset($errors) ? $errors->getBag('subscribe')->first('contact') : null;
@endphp

<footer class="mt-16 border-t border-sand-200 bg-sand-100">
    <section id="newsletter" class="bg-maroon-800 text-white" aria-labelledby="newsletter-title">
        <div class="page-container grid gap-5 py-10 lg:grid-cols-2 lg:items-center lg:gap-12">
            <div>
                <h2 id="newsletter-title" class="font-display text-2xl">{{ __('Join our community') }}</h2>
                <p class="mt-1 text-sm text-white/80">{{ __('Be the first to hear about new arrivals and offers.') }}</p>
            </div>
            <form method="POST" action="{{ route('subscribers.store') }}" x-data="submitOnce" x-on:submit="submit($event)" x-on:pageshow.window="submitting = false">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="newsletter-website">Website</label>
                    <input id="newsletter-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="flex gap-2">
                    <label for="newsletter-contact" class="sr-only">{{ __('WhatsApp number or email') }}</label>
                    <input
                        id="newsletter-contact"
                        name="contact"
                        value="{{ old('contact') }}"
                        placeholder="{{ __('WhatsApp number or email') }}"
                        maxlength="190"
                        required
                        dir="auto"
                        autocomplete="email"
                        class="h-12 min-w-0 flex-1 rounded-full border-0 bg-white px-5 text-base text-ink-900 placeholder:text-ink-500 focus:ring-2 focus:ring-gold-300 focus:outline-none"
                    >
                    <x-store.button type="submit" variant="light" x-bind:disabled="submitting">
                        <span x-show="! submitting">{{ __('Join') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('Joining…') }}</span>
                    </x-store.button>
                </div>
                @if ($subscribeError)
                    <p class="mt-2 text-sm font-medium text-gold-300" role="alert">{{ $subscribeError }}</p>
                @endif
                @if (session('subscribe_status'))
                    <p class="mt-2 text-sm font-medium" role="status">{{ session('subscribe_status') }}</p>
                @endif
                <p class="mt-2 text-xs text-white/70">{{ __('By joining, you agree to receive news and offers on WhatsApp or by email. Ask us to stop at any time.') }}</p>
            </form>
        </div>
    </section>

    <div class="page-container grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-5">
        <div class="space-y-3">
            <x-store.logo />
            <p class="max-w-xs text-sm text-ink-500">
                {{ __('Jelabiyas in many colors, delivered across Qatar. Pay cash on delivery.') }}
            </p>
        </div>

        <div>
            <h2 class="text-sm font-semibold">{{ __('Shop') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-700">
                <li><a href="{{ route('shop') }}" class="hover:text-maroon-700">{{ __('Shop all') }}</a></li>
                @foreach ($navigation['categories'] as $category)
                    <li><a href="{{ route('category.show', $category) }}" class="hover:text-maroon-700">{{ $category->translated('name') }}</a></li>
                @endforeach
                <li><a href="{{ route('wishlist') }}" class="hover:text-maroon-700">{{ __('Wishlist') }}</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold">{{ __('Help') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-700">
                <li><a href="{{ route('track.show') }}" class="hover:text-maroon-700">{{ __('Track your order') }}</a></li>
                <li><a href="{{ route('pages.faq') }}" class="hover:text-maroon-700">{{ __('Frequently asked questions') }}</a></li>
                <li><a href="{{ route('contact.show') }}" class="hover:text-maroon-700">{{ __('Contact us') }}</a></li>
                @foreach ($navigation['pages'] as $page)
                    <li><a href="{{ route('pages.show', $page) }}" class="hover:text-maroon-700">{{ $page->translated('title') }}</a></li>
                @endforeach
            </ul>
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

        <div class="space-y-8">
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

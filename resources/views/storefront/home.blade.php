<x-store.layout :description="__('Jelabiyas in many colors, delivered across Qatar. Pay cash on delivery.')">
    <section class="relative overflow-hidden bg-maroon-800 text-white">
        <div class="bg-motif-light absolute inset-0" aria-hidden="true"></div>

        <div class="page-container relative py-20 sm:py-28">
            <p class="text-sm font-medium text-gold-300 ltr:uppercase ltr:tracking-[0.2em]">
                {{ __('New season') }}
            </p>
            <h1 class="mt-4 max-w-xl font-display text-4xl leading-tight text-balance sm:text-5xl">
                {{ __('Jelabiyas in the colors you love') }}
            </h1>
            <p class="mt-5 max-w-md text-base text-white/80">
                {{ __('Pick your color and size, order in a minute, and pay in cash when it arrives.') }}
            </p>
            <p class="mt-8 inline-flex items-center gap-2 rounded-full border border-white/25 px-4 py-2 text-sm text-white/90">
                <span class="size-2 rounded-full bg-gold-300"></span>
                {{ __('Our online collection opens soon') }}
            </p>
        </div>
    </section>

    <section class="page-container -mt-8 relative">
        <x-store.trust-strip class="shadow-sm" />
    </section>

    <section class="page-container py-16 text-center">
        <x-store.motif-divider />
        <h2 class="mt-6 font-display text-2xl sm:text-3xl">{{ __('Our collection is on its way') }}</h2>
        <p class="mx-auto mt-3 max-w-md text-ink-500">
            {{ __('We are photographing every jelabiya in every color. Follow us to see them first.') }}
        </p>
    </section>
</x-store.layout>

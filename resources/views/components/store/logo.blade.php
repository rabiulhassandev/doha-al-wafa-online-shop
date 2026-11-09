@inject('settings', 'App\Services\Settings')

{{-- Text wordmark until the client supplies their logo files. --}}
<span {{ $attributes->merge(['class' => 'font-brand text-xl leading-none tracking-wide text-maroon-700 sm:text-2xl']) }} dir="ltr">
    {{ $settings->get('store_name') }}
</span>

@inject('settings', 'App\Services\Settings')

@php($message = $settings->get('announcement_'.app()->getLocale()))

@if (filled($message))
    <div class="bg-maroon-800 px-4 py-2 text-center text-xs font-medium text-white sm:text-sm">
        {{ $message }}
    </div>
@endif

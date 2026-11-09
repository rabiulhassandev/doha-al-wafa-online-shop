{{-- Plain table layout with inline styles, which email apps display reliably. Right-to-left for Arabic. --}}
@props(['title', 'preheader' => null, 'footer' => null])

@inject('settings', 'App\Services\Settings')

@php
    $locale = app()->getLocale();
    $direction = config("shop.locales.{$locale}.direction", 'ltr');
    $align = $direction === 'rtl' ? 'right' : 'left';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #fbf8f3; color: #1e1a17; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, Arial, sans-serif;">
        @if ($preheader)
            <div style="display: none; max-height: 0; overflow: hidden;">{{ $preheader }}</div>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fbf8f3;">
            <tr>
                <td align="center" style="padding: 24px 12px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $direction }}" style="max-width: 560px; background-color: #ffffff; border-radius: 16px;">
                        <tr>
                            <td style="padding: 24px 24px 0; text-align: {{ $align }}; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; color: #7a1e35;">
                                {{ $settings->get('store_name') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 24px 24px; text-align: {{ $align }}; font-size: 15px; line-height: 1.6;">
                                {{ $slot }}
                            </td>
                        </tr>
                    </table>
                    @if ($footer)
                        <p dir="{{ $direction }}" style="max-width: 560px; margin: 16px auto 0; font-size: 12px; line-height: 1.5; color: #6b625a;">{{ $footer }}</p>
                    @endif
                </td>
            </tr>
        </table>
    </body>
</html>

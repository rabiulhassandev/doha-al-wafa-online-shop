{{--
    Error pages that must work when the store itself is down: no database, session, cache or built assets.
    The message shows in English and Arabic, since the language may not be known.
    Expects $titleEn, $messageEn, $titleAr and $messageAr.
--}}
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $titleEn }} · {{ config('app.name') }}</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; background: #fbf8f3; color: #1e1a17; font-family: system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; line-height: 1.6; }
            main { width: 100%; max-width: 28rem; text-align: center; }
            .brand { font-family: Georgia, serif; font-size: 1.5rem; color: #7a1e35; }
            section { margin-top: 1.5rem; padding: 1.5rem; border: 1px solid #eadfcf; border-radius: 1rem; background: #fff; }
            section + section { margin-top: 0.75rem; }
            h1, h2 { margin: 0; font-size: 1.125rem; }
            p { margin: 0.5rem 0 0; color: #6b625a; font-size: 0.9375rem; }
            a { display: inline-flex; margin-top: 1.5rem; padding: 0.75rem 1.5rem; border-radius: 999px; background: #7a1e35; color: #fff; text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <main>
            <p class="brand">{{ config('app.name') }}</p>

            <section>
                <h1>{{ $titleEn }}</h1>
                <p>{{ $messageEn }}</p>
            </section>
            <section lang="ar" dir="rtl">
                <h2>{{ $titleAr }}</h2>
                <p>{{ $messageAr }}</p>
            </section>

            <a href="/">Back to the store · العودة إلى المتجر</a>
        </main>
    </body>
</html>

@php
    $page = app(App\Services\ErrorPageManager::class)->get($statusCode);
    $requestId = request()->attributes->get('request_id', 'tidak-tersedia');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $statusCode }} — {{ $page['title'] }}</title>
    <style>
        :root { color-scheme: light dark; --accent: {{ $page['accent_color'] }}; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f8fafc; color: #0f172a; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .card { width: min(100%, 680px); overflow: hidden; border: 1px solid #e2e8f0; border-radius: 28px; background: #fff; box-shadow: 0 24px 70px rgba(15, 23, 42, .12); }
        .bar { height: 7px; background: var(--accent); }
        .content { padding: clamp(32px, 7vw, 64px); text-align: center; }
        .icon { width: 82px; height: 82px; display: grid; place-items: center; margin: 0 auto 24px; border-radius: 24px; color: var(--accent); background: color-mix(in srgb, var(--accent) 12%, transparent); }
        .code { margin: 0; color: var(--accent); font-size: 14px; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
        h1 { margin: 10px 0 14px; font-size: clamp(28px, 5vw, 42px); line-height: 1.15; }
        .message { max-width: 540px; margin: 0 auto; color: #64748b; font-size: 16px; line-height: 1.75; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 30px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px; border: 0; border-radius: 12px; cursor: pointer; text-decoration: none; font: inherit; font-weight: 700; }
        .primary { background: var(--accent); color: #fff; }
        .secondary { background: #e2e8f0; color: #334155; }
        .reference { margin: 28px 0 0; color: #94a3b8; font-size: 12px; overflow-wrap: anywhere; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e2e8f0; } .card { border-color: #334155; background: #1e293b; } .message { color: #94a3b8; } .secondary { background: #334155; color: #e2e8f0; } }
    </style>
</head>
<body>
    <main class="card">
        <div class="bar"></div>
        <div class="content">
            <div class="icon" aria-hidden="true">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/>
                </svg>
            </div>
            <p class="code">Error {{ $statusCode }}</p>
            <h1>{{ $page['title'] }}</h1>
            <p class="message">{{ $page['message'] }}</p>
            <div class="actions">
                @if (filled($page['button_label']) && filled($page['button_url']))
                    <a href="{{ $page['button_url'] }}" class="button primary">{{ $page['button_label'] }}</a>
                @endif
                @if ($page['show_back_button'])
                    <button type="button" class="button secondary" onclick="history.back()">Kembali</button>
                @endif
            </div>
            <p class="reference">Kode referensi: {{ $requestId }}</p>
        </div>
    </main>
</body>
</html>

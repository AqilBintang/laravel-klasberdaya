<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Terjadi Kesalahan') — {{ config('app.name', 'Klas Berdaya') }}</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            color: @yield('code-color', '#0ea5e9');
            letter-spacing: -0.04em;
        }
        .title {
            font-size: 1.375rem;
            font-weight: 700;
            margin-top: 1rem;
            color: #0f172a;
        }
        .message {
            margin-top: 0.75rem;
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.65rem 1.75rem;
            background: @yield('btn-color', '#0ea5e9');
            color: #fff;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .footer {
            margin-top: 2.5rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">@yield('code')</div>
        <div class="title">@yield('title')</div>
        <div class="message">@yield('message')</div>
        @yield('action')
    </div>
    <p class="footer">© {{ date('Y') }} {{ config('app.name', 'Klas Berdaya') }}</p>
</body>
</html>

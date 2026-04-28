<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Админ') — RC PM Bot</title>
    <style>
        :root { --bg: #0f1419; --card: #1a2332; --text: #e7ecf3; --muted: #8b9bb0; --accent: #3d8bfd; --border: #2a3544; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header { background: var(--card); border-bottom: 1px solid var(--border); padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
        header nav { display: flex; gap: 1rem; flex-wrap: wrap; }
        main { max-width: 960px; margin: 0 auto; padding: 1.5rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; }
        h1 { font-size: 1.35rem; margin: 0 0 1rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th, td { text-align: left; padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 600; }
        .btn { display: inline-block; padding: 0.45rem 0.9rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; background: var(--accent); color: #fff; }
        .btn.secondary { background: var(--border); color: var(--text); }
        .btn.danger { background: #c0392b; }
        input[type="text"], input[type="password"], input[type="date"], select, textarea { width: 100%; max-width: 100%; padding: 0.5rem 0.65rem; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--text); }
        textarea { min-height: 120px; font-family: inherit; }
        .muted { color: var(--muted); font-size: 0.9rem; }
        .error { color: #e74c3c; margin: 0.25rem 0; }
        .ok { color: #2ecc71; margin-bottom: 1rem; }
        label { display: block; margin: 0.5rem 0 0.25rem; color: var(--muted); font-size: 0.85rem; }
        code { font-size: 0.9em; background: var(--bg); padding: 0.1em 0.35em; border-radius: 4px; border: 1px solid var(--border); }
        form.inline { display: inline; }
    </style>
    @stack('styles')
</head>
<body>
<header>
    <strong>RC PM Bot</strong>
    @if(session('admin_auth'))
        <nav>
            <a href="{{ route('admin.channels') }}">Каналы</a>
            <a href="{{ route('admin.facts') }}">Факты</a>
            <a href="{{ route('admin.schedule-exceptions') }}">Дни исключений</a>
            <a href="{{ route('admin.absence-periods') }}">Периоды отсутствий</a>
        </nav>
        <form action="{{ route('admin.logout') }}" method="post" class="inline">
            @csrf
            <button type="submit" class="btn secondary">Выйти</button>
        </form>
    @endif
</header>
<main>
    @if(session('status'))
        <p class="ok">{{ session('status') }}</p>
    @endif
    @yield('content')
</main>
</body>
</html>

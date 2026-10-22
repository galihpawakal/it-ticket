<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tiket') — {{ config('app.name') }}</title>
    <style>
        :root {
            --ink: #17212b; --muted: #5b6875; --paper: #f2f4f5; --surface: #fff; --line: #d8dee3;
            --accent: #0b6e6e; --accent-ink: #fff;
            --late: #b42318; --late-bg: #fdf0ee;
            --open: #175cd3; --open-bg: #e8f0fe;
            --prog: #8a4b08; --prog-bg: #fdf0dc;
            --done: #067647; --done-bg: #e3f5ec;
            --low: #475467; --med: #8a4b08; --high: #b42318;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); line-height: 1.5;
               font: 16px/1.5 ui-sans-serif, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        a { color: var(--accent); }
        .top { background: var(--ink); color: #fff; }
        .top .in { max-width: 1080px; margin: 0 auto; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .brand { color: #fff; text-decoration: none; font-weight: 700; font-size: 18px; letter-spacing: .2px; }
        .brand small { font-weight: 400; opacity: .7; margin-left: 8px; font-size: 13px; }
        main { max-width: 1080px; margin: 0 auto; padding: 24px 20px 56px; }
        h1 { font-size: 24px; margin: 0 0 16px; }
        .btn { display: inline-block; border: 1px solid var(--line); background: var(--surface); color: var(--ink); padding: 8px 14px; border-radius: 6px;
               font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn:hover { border-color: var(--ink); }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: var(--accent-ink); }
        .btn.primary:hover { filter: brightness(1.1); }
        .btn[disabled], .btn.disabled { opacity: .45; cursor: not-allowed; }
        :focus-visible { outline: 3px solid #f5b83d; outline-offset: 2px; }

        .flash { border-left: 4px solid var(--done); background: var(--done-bg); padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
        .errors { border-left: 4px solid var(--late); background: var(--late-bg); padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
        .errors ul { margin: 6px 0 0 18px; padding: 0; }

        .tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .tab { text-decoration: none; color: var(--ink); background: var(--surface); border: 1px solid var(--line); border-radius: 999px; padding: 4px 12px; font-size: 14px; }
        .tab b { margin-left: 4px; color: var(--muted); }
        .tab.active { background: var(--ink); color: #fff; border-color: var(--ink); }
        .tab.active b { color: #cfd6dc; }
        .search { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .search input[type=search] { flex: 1 1 240px; }

        input[type=text], input[type=search], input[type=datetime-local], select, textarea {
            width: 100%; font: inherit; padding: 9px 10px; border: 1px solid #aab4bd; border-radius: 6px; background: #fff; color: var(--ink); }
        textarea { min-height: 130px; resize: vertical; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-weight: 600; margin-bottom: 4px; }
        .field .hint { color: var(--muted); font-size: 13px; margin-top: 3px; }
        .field .err { color: var(--late); font-size: 14px; margin-top: 4px; }
        .field.bad input, .field.bad select, .field.bad textarea { border-color: var(--late); background: var(--late-bg); }
        .req { color: var(--late); }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 760px; }
        th { text-align: left; font-size: 13px; color: var(--muted); font-weight: 600; padding: 10px 12px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        tr.late td:first-child { box-shadow: inset 4px 0 0 var(--late); }
        tr.late { background: var(--late-bg); }
        td .title { font-weight: 600; color: var(--ink); text-decoration: none; }
        td .title:hover { text-decoration: underline; }
        .sub { color: var(--muted); font-size: 13px; }
        .empty { padding: 40px 20px; text-align: center; color: var(--muted); }

        .chip { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 13px; font-weight: 600; white-space: nowrap; }
        .st-open { background: var(--open-bg); color: var(--open); }
        .st-in_progress { background: var(--prog-bg); color: var(--prog); }
        .st-done { background: var(--done-bg); color: var(--done); }
        .pr { font-weight: 600; }
        .pr-low { color: var(--low); } .pr-medium { color: var(--med); } .pr-high { color: var(--high); }
        .late-chip { background: var(--late); color: #fff; }
        .note-late { color: var(--late); font-weight: 600; }

        .pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px; color: var(--muted); }

        dl.detail { display: grid; grid-template-columns: 160px 1fr; gap: 12px 16px; margin: 0; padding: 20px; }
        dl.detail dt { color: var(--muted); font-size: 14px; }
        dl.detail dd { margin: 0; white-space: pre-line; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 16px 20px; border-top: 1px solid var(--line); }
        .actions form { display: contents; }
        form.card { padding: 20px; max-width: 680px; }
        @media (max-width: 560px) { dl.detail { grid-template-columns: 1fr; gap: 2px; } dl.detail dd { margin-bottom: 10px; } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
<header class="top">
    <div class="in">
        <a class="brand" href="{{ route('tickets.index') }}">Helpdesk IT<small>zona waktu WIB</small></a>
        <a class="btn primary" href="{{ route('tickets.create') }}">Buat tiket</a>
    </div>
</header>
<main>
    @if (session('success'))
        <div class="flash" role="status">{{ session('success') }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>

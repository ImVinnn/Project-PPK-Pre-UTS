<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Peminjaman Fasilitas')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,500;8..60,600;8..60,700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1b2a36;
            --ink-soft: #56636d;
            --paper: #f5f3ed;
            --panel: #fffdf9;
            --brass: #a9752f;
            --brass-dark: #8a5f26;
            --moss: #3f7a52;
            --amber: #c98a2c;
            --line: #ddd6c7;
            --text: #22262b;
        }

        body {
            background: var(--paper);
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
        }

        .font-serif {
            font-family: 'Source Serif 4', serif;
        }

        .font-mono {
            font-family: 'IBM Plex Mono', monospace;
        }

        .site-header {
            background: var(--ink);
            border-bottom: 3px solid var(--brass);
        }

        .site-header .wordmark {
            font-family: 'Source Serif 4', serif;
            font-weight: 600;
            font-size: 1.15rem;
            color: #f5f3ed;
            text-decoration: none;
            letter-spacing: 0.01em;
        }

        .site-header .wordmark:hover {
            color: var(--brass);
        }

        .btn-brass {
            background: transparent;
            border: 1px solid #6d7986;
            color: #f5f3ed;
        }

        .btn-brass:hover {
            background: var(--brass);
            border-color: var(--brass);
            color: #1b2a36;
        }

        .btn-primary {
            background: var(--brass);
            border-color: var(--brass);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--brass-dark);
            border-color: var(--brass-dark);
        }

        a {
            color: var(--brass-dark);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--brass);
            box-shadow: 0 0 0 0.2rem rgba(169, 117, 47, 0.2);
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container d-flex align-items-center justify-content-between py-3">
            <a class="wordmark" href="/">Fasilitas Kampus</a>
            <a class="btn btn-brass btn-sm px-3" href="/login">Login</a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

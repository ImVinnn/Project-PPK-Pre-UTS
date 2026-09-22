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

        .site-header .nav-link {
            color: #f5f3ed;
        }

        .site-header .nav-link:hover,
        .site-header .nav-link.active-page {
            color: var(--brass);
        }

        .site-header .navbar-toggler {
            border-color: #6d7986;
        }

        .site-header .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(245, 243, 239, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg site-header">
        <div class="container-fluid px-4 py-2">
            <a class="navbar-brand wordmark" href="{{ route('facilities.index') }}">Fasilitas Kampus</a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Buka menu navigasi"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                @guest
                    <ul class="navbar-nav me-auto mt-3 mt-lg-0">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('facilities.index') ? 'active-page' : '' }}" href="{{ route('facilities.index') }}">
                                Katalog Fasilitas
                            </a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2 mt-3 mt-lg-0">
                        <a class="btn btn-brass btn-sm px-3" href="{{ route('register') }}">Daftar</a>
                        <a class="btn btn-brass btn-sm px-3" href="{{ route('login') }}">Login</a>
                    </div>
                @else
                    <ul class="navbar-nav me-auto mt-3 mt-lg-0">
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_ADMIN))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active-page' : '' }}" href="{{ route('admin.accounts.index') }}">
                                    Kelola Akun
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.faculties.*') ? 'active-page' : '' }}" href="{{ route('admin.faculties.index') }}">
                                    Fakultas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.buildings.*') ? 'active-page' : '' }}" href="{{ route('admin.buildings.index') }}">
                                    Gedung
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.facilities.*') ? 'active-page' : '' }}" href="{{ route('admin.facilities.index') }}">
                                    Fasilitas
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('facilities.index') ? 'active-page' : '' }}" href="{{ route('facilities.index') }}">
                                    Katalog Fasilitas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reservations.index') ? 'active-page' : '' }}" href="{{ route('reservations.index') }}">
                                    Reservasi Saya
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reservations.create') ? 'active-page' : '' }}" href="{{ route('reservations.create') }}">
                                    + Ajukan Reservasi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active-page' : '' }}" href="{{ route('reports.index') }}">
                                    Laporan Saya
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_OFFICER))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('officer.dashboard') ? 'active-page' : '' }}" href="{{ route('officer.dashboard') }}">
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('officer.reservations.*') ? 'active-page' : '' }}" href="{{ route('officer.reservations.index') }}">
                                    Antrean Reservasi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('officer.reports.*') ? 'active-page' : '' }}" href="{{ route('officer.reports.index') }}">
                                    Laporan Kerusakan
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                        <span class="small" style="color: #c7cdd2;">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-brass btn-sm px-3">Logout</button>
                        </form>
                    </div>
                @endguest
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

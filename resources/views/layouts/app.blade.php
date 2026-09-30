<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Peminjaman Fasilitas Kampus')</title>

    {{-- Bootstrap 5.3 Framework for Responsive Grid --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Google Fonts: Plus Jakarta Sans & JetBrains Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Sistem Desain Fotel --}}
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body>
    {{-- Header Bersih Bergaya Fotel --}}
    <header class="fotel-header">
        <div class="fotel-container py-3">
            <div class="d-flex align-items-center justify-content-between">
                {{-- Brand & Lokasi --}}
                <div class="d-flex align-items-center gap-3">
                    <a class="fotel-brand text-decoration-none" href="{{ route('facilities.index') }}">
                        <div class="fotel-logo-icon" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span>Fasilitas Kampus</span>
                    </a>

                    <div class="fotel-location-chip d-none d-sm-inline-flex" title="Lokasi Kampus Operasional">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>Kampus Terpadu</span>
                    </div>
                </div>

                {{-- Hamburger Mobile Button --}}
                <button
                    class="d-lg-none btn btn-sm btn-fotel-secondary px-3"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#fotelNavCollapse"
                    aria-controls="fotelNavCollapse"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                {{-- Navigasi Desktop --}}
                <div class="d-none d-lg-flex align-items-center gap-2">
                    @guest
                        <a class="fotel-nav-link {{ request()->routeIs('facilities.index') ? 'active-page' : '' }}" href="{{ route('facilities.index') }}">
                            Katalog Fasilitas
                        </a>
                        <div class="ms-3 d-flex align-items-center gap-2">
                            <a class="btn-fotel-outline" href="{{ route('register') }}">Daftar</a>
                            <a class="btn-fotel-gold" href="{{ route('login') }}">Login</a>
                        </div>
                    @else
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_ADMIN))
                            <a class="fotel-nav-link {{ request()->routeIs('admin.accounts.*') ? 'active-page' : '' }}" href="{{ route('admin.accounts.index') }}">
                                Kelola Akun
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('admin.faculties.*') ? 'active-page' : '' }}" href="{{ route('admin.faculties.index') }}">
                                Fakultas
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('admin.buildings.*') ? 'active-page' : '' }}" href="{{ route('admin.buildings.index') }}">
                                Gedung
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('admin.facilities.*') ? 'active-page' : '' }}" href="{{ route('admin.facilities.index') }}">
                                Fasilitas
                            </a>
                        @endif

                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                            <a class="fotel-nav-link {{ request()->routeIs('facilities.index') ? 'active-page' : '' }}" href="{{ route('facilities.index') }}">
                                Katalog Fasilitas
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('reservations.index') ? 'active-page' : '' }}" href="{{ route('reservations.index') }}">
                                Reservasi Saya
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('reservations.create') ? 'active-page' : '' }}" href="{{ route('reservations.create') }}">
                                + Ajukan Reservasi
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('reports.*') ? 'active-page' : '' }}" href="{{ route('reports.index') }}">
                                Laporan Saya
                            </a>
                        @endif

                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_OFFICER))
                            <a class="fotel-nav-link {{ request()->routeIs('officer.dashboard') ? 'active-page' : '' }}" href="{{ route('officer.dashboard') }}">
                                Dashboard
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('officer.reservations.*') ? 'active-page' : '' }}" href="{{ route('officer.reservations.index') }}">
                                Antrean Reservasi
                            </a>
                            <a class="fotel-nav-link {{ request()->routeIs('officer.reports.*') ? 'active-page' : '' }}" href="{{ route('officer.reports.index') }}">
                                Laporan Kerusakan
                            </a>
                        @endif

                        {{-- User Chip & Logout --}}
                        <div class="ms-3 ps-3 border-start d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="fw-bold small lh-1 text-dark">{{ auth()->user()->name }}</div>
                                <span class="fotel-badge fotel-badge-slate text-uppercase mt-1" style="font-size: 0.65rem;">
                                    {{ auth()->user()->role }}
                                </span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn-fotel-secondary py-1 px-3" style="font-size: 0.75rem;">
                                    Logout
                                </button>
                            </form>
                        </div>
                    @endguest
                </div>
            </div>

            {{-- Mobile Nav Collapse --}}
            <div class="collapse d-lg-none mt-3 pt-3 border-top" id="fotelNavCollapse">
                @guest
                    <div class="d-flex flex-column gap-2 mb-3">
                        <a class="fotel-nav-link px-0" href="{{ route('facilities.index') }}">Katalog Fasilitas</a>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn-fotel-outline flex-fill text-center" href="{{ route('register') }}">Daftar</a>
                        <a class="btn-fotel-gold flex-fill text-center" href="{{ route('login') }}">Login</a>
                    </div>
                @else
                    <div class="d-flex flex-column gap-2 mb-3">
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_ADMIN))
                            <a class="fotel-nav-link px-0" href="{{ route('admin.accounts.index') }}">Kelola Akun</a>
                            <a class="fotel-nav-link px-0" href="{{ route('admin.faculties.index') }}">Fakultas</a>
                            <a class="fotel-nav-link px-0" href="{{ route('admin.buildings.index') }}">Gedung</a>
                            <a class="fotel-nav-link px-0" href="{{ route('admin.facilities.index') }}">Fasilitas</a>
                        @endif
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                            <a class="fotel-nav-link px-0" href="{{ route('facilities.index') }}">Katalog Fasilitas</a>
                            <a class="fotel-nav-link px-0" href="{{ route('reservations.index') }}">Reservasi Saya</a>
                            <a class="fotel-nav-link px-0" href="{{ route('reservations.create') }}">+ Ajukan Reservasi</a>
                            <a class="fotel-nav-link px-0" href="{{ route('reports.index') }}">Laporan Saya</a>
                        @endif
                        @if (auth()->user()->hasRole(\App\Support\Status::ROLE_OFFICER))
                            <a class="fotel-nav-link px-0" href="{{ route('officer.dashboard') }}">Dashboard</a>
                            <a class="fotel-nav-link px-0" href="{{ route('officer.reservations.index') }}">Antrean Reservasi</a>
                            <a class="fotel-nav-link px-0" href="{{ route('officer.reports.index') }}">Laporan Kerusakan</a>
                        @endif
                    </div>
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn-fotel-secondary py-1 px-3">Logout</button>
                        </form>
                    </div>
                @endguest
            </div>
        </div>
    </header>

    {{-- Main Content View --}}
    <main id="main-content" class="fotel-page-transition">
        @yield('content')
    </main>

    {{-- Footer Hitam Arang Bergaya Fotel --}}
    <footer class="fotel-footer">
        <div class="fotel-container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="fotel-logo-icon" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="fs-5 fw-bold text-white tracking-tight">Fasilitas Kampus</span>
                    </div>
                    <p class="small mb-0" style="max-width: 38ch; line-height: 1.7; color: #CBD5E1;">
                        Portal terintegrasi pengelolaan ruang kelas, laboratorium riset, aula serbaguna, dan peralatan kampus dengan sistem alokasi 26 slot waktu harian.
                    </p>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="fotel-footer-title">Navigasi</div>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li><a href="{{ route('facilities.index') }}">Katalog Fasilitas</a></li>
                        @auth
                            @if(auth()->user()->hasRole(\App\Support\Status::ROLE_USER))
                                <li><a href="{{ route('reservations.index') }}">Reservasi Saya</a></li>
                                <li><a href="{{ route('reservations.create') }}">Ajukan Peminjaman</a></li>
                                <li><a href="{{ route('reports.index') }}">Laporan Kerusakan</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('login') }}">Masuk Pengguna</a></li>
                            <li><a href="{{ route('register') }}">Registrasi Akun</a></li>
                        @endauth
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="fotel-footer-title">Jam Operasional</div>
                    <ul class="fotel-footer-list small d-flex flex-column gap-2">
                        <li style="color: #94A3B8;">Senin - Jumat: <strong class="text-white font-mono ms-1">07.00 - 20.00 WIB</strong></li>
                        <li style="color: #94A3B8;">Sabtu - Minggu: <strong class="text-white font-mono ms-1">Dengan Dispensasi</strong></li>
                        <li style="color: #94A3B8;">Total Alokasi: <span class="text-white ms-1">26 Slot per Hari (@ 30 mnt)</span></li>
                        <li style="color: #94A3B8;">Batas Batal Mandiri: <span class="text-warning ms-1">Maks. 2 Jam Sebelum Mulai</span></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <div class="fotel-footer-title">Layanan & Kontak</div>
                    <p class="small mb-2" style="color: #CBD5E1; line-height: 1.7;">
                        Gedung Rektorat & Layanan Akademik Kampus Terpadu Lt. 1<br>
                        Telp: <span class="text-white">(021) 7888-KAMPUS</span><br>
                        Email: <span class="text-white">fasilitas@kampus.ac.id</span>
                    </p>
                    <div class="small font-mono mt-3" style="color: #94A3B8;">
                        *Data agenda bersifat rahasia sivitas
                    </div>
                </div>
            </div>

            <div class="fotel-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div style="color: #94A3B8;">&copy; {{ date('Y') }} Sistem Peminjaman Fasilitas Kampus. Hak cipta dilindungi.</div>
                <div class="d-flex gap-3">
                    <a href="{{ route('facilities.index') }}">Kebijakan Privasi</a>
                    <span style="color: #475569;">&middot;</span>
                    <a href="{{ route('facilities.index') }}">Ketentuan Layanan</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Bootstrap JS Bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/motion.js') }}" defer></script>
</body>
</html>

@props(['settings', 'title' => null, 'description' => null])
@php
    $isHome = request()->routeIs('home');
    $siteTitle = $settings->meta_title ?: $settings->name;
    $metaDescription = $description ?: $settings->meta_description ?: $settings->tagline;
    $ogImage = $settings->og_image_path
        ? Storage::disk('public')->url($settings->og_image_path)
        : ($settings->hero_image_path ? Storage::disk('public')->url($settings->hero_image_path) : asset('images/simkopdes/hero.webp'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($settings->favicon_path)
        <link rel="icon" href="{{ Storage::disk('public')->url($settings->favicon_path) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endif
    <title>{{ $title ? $title.' — ' : '' }}{{ $siteTitle }}{{ $settings->provinceName() ? ' · '.$settings->provinceName() : '' }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if($settings->meta_keywords)
        <meta name="keywords" content="{{ $settings->meta_keywords }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->name }}">
    <meta property="og:title" content="{{ $title ? $title.' — '.$siteTitle : $siteTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ? $title.' — '.$siteTitle : $siteTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $isHome ? 'home-page' : 'inner-page' }}">
    <a class="skip-link" href="#main">Lewati ke konten</a>
    <header class="header">
        <div class="topbar"><div class="container topbar-inner">
            @if($settings->regionBreadcrumb())
                <span class="topbar-item"><x-app-icon name="pin" />{{ $settings->regionBreadcrumb() }}</span>
            @endif
            <span class="topbar-item topbar-phone"><x-app-icon name="phone" />{{ $settings->phone ?: '(021) 1500 587' }}</span>
            <span class="topbar-links">
                <a href="{{ url('/#tentang') }}">Tentang Kami</a>
                <a href="{{ url('/#kontak') }}">Kontak</a>
            </span>
        </div></div>
        <div class="header-inner"><div class="container header-inner-content">
            <a class="brand" href="{{ route('home') }}" aria-label="Beranda {{ $settings->name }}">
                <img src="{{ $settings->logo_path ? Storage::disk('public')->url($settings->logo_path) : asset('images/simkopdes/simkopdes-white.webp') }}" alt="{{ $settings->name }}" width="220" height="42">
            </a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navigation" aria-label="Buka menu"><span>Menu</span><x-app-icon name="menu" /></button>
            <nav id="navigation" aria-label="Navigasi utama">
                <details class="nav-dropdown"><summary>Beranda <x-app-icon name="chevron" /></summary><div class="dropdown-panel"><a href="{{ url('/#tentang') }}">Tentang Koperasi</a><a href="{{ url('/#jaringan') }}">Jaringan Koperasi</a><a href="{{ url('/#manfaat') }}">Manfaat Program</a><a href="{{ url('/#regulasi') }}">Regulasi</a><a href="{{ route('news.index') }}">Berita</a><a href="{{ url('/#kontak') }}">Kontak</a></div></details>
                <a href="{{ url('/#statistik') }}">Statistik</a>
                <a href="{{ route('data-kopdes') }}">Data Kopdes</a>
                <a class="nav-mobile-app" href="{{ url('/#aplikasi') }}"><x-app-icon name="download" /> Simkopdes Mobile</a>
                <a class="nav-login" href="{{ url('/admin') }}">Masuk</a>
            </nav>
        </div></div>
    </header>
    <main id="main">{{ $slot }}</main>
    <footer id="kontak" class="site-footer">
        <div class="container footer-grid">
            <div><h2>Tautan Cepat</h2><a href="https://simkopdes.go.id/pers/dashboard" target="_blank" rel="noopener noreferrer">Dasbor Simkopdes ↗</a><a href="{{ url('/#statistik') }}">Statistik nasional</a><a href="{{ url('/#aplikasi') }}">Unduh aplikasi</a><a href="https://lms.kop.go.id/" target="_blank" rel="noopener noreferrer">Kemenkop Corporate University ↗</a></div>
            <div><h2>Informasi</h2><a href="{{ url('/#jaringan') }}">Direktori koperasi</a><a href="{{ url('/#regulasi') }}">Dokumen dan regulasi</a><a href="{{ route('news.index') }}">Berita koperasi</a><a href="https://simkopdes.go.id/kontak" target="_blank" rel="noopener noreferrer">Kontak Satgas nasional ↗</a><a href="https://simkopdes.go.id/syarat-dan-ketentuan" target="_blank" rel="noopener noreferrer">Ketentuan Simkopdes ↗</a></div>
            <div><h2>Media Sosial</h2><p class="footer-caption">Kanal Kementerian Koperasi</p><div class="social-links"><a href="https://www.instagram.com/kemenkop/" target="_blank" rel="noopener noreferrer" aria-label="Instagram Kementerian Koperasi"><x-app-icon name="instagram" /></a><a href="https://www.youtube.com/@KemenkopRI" target="_blank" rel="noopener noreferrer" aria-label="YouTube Kementerian Koperasi"><x-app-icon name="play" /></a><a href="https://www.facebook.com/kemenkopukm" target="_blank" rel="noopener noreferrer" aria-label="Facebook Kementerian Koperasi">f</a><a href="https://twitter.com/kemenkop_" target="_blank" rel="noopener noreferrer" aria-label="X Kementerian Koperasi">X</a></div><a class="help-button" href="https://simkopdes.go.id/kontak" target="_blank" rel="noopener noreferrer"><x-app-icon name="phone" /><span>Kontak Simkopdes<br><strong>(021) 1500 587</strong></span></a></div>
            <div class="footer-contact"><h2>{{ $settings->address || $settings->email || $settings->phone ? 'Kontak Wilayah' : 'Satgas KDKMP' }}</h2>
                @if($settings->address || $settings->email || $settings->phone)
                    <strong>{{ $settings->name }}</strong>
                    @if($settings->address)<p>{{ $settings->address }}</p>@endif
                    @if($settings->email)<a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a>@endif
                    @if($settings->phone)<a href="tel:{{ preg_replace('/[^+0-9]/', '', $settings->phone) }}">{{ $settings->phone }}</a>@endif
                    @if($settings->hours)<p>{{ $settings->hours }}</p>@endif
                @else
                    <img class="organizations" src="{{ asset('images/simkopdes/organizations.webp') }}" alt="Instansi dalam Satgas KDKMP pada portal Simkopdes" loading="lazy" width="640" height="423">
                    <div class="address"><strong>Alamat Satgas nasional</strong><p>Graha Mandiri Lt. 3, Jl. Imam Bonjol No. 61, Menteng, Jakarta Pusat 10310.</p><a href="https://simkopdes.go.id/kontak" target="_blank" rel="noopener noreferrer">Lihat kontak resmi ↗</a></div>
                @endif
            </div>
        </div>
        <div class="footer-bottom"><div class="container"><div><p>© {{ date('Y') }} {{ $settings->name }}</p><a href="https://simkopdes.go.id/" target="_blank" rel="noopener noreferrer">Referensi informasi dan identitas visual: Simkopdes ↗</a></div><img src="{{ asset('images/simkopdes/pedoman.webp') }}" alt="Kementerian Koperasi Republik Indonesia" loading="lazy" width="1835" height="603"></div></div>
    </footer>
</body>
</html>

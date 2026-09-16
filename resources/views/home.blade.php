<x-layout :settings="$settings">
    <section class="hero" aria-labelledby="hero-title" style="background-image: url('{{ $settings->hero_image_path ? Storage::disk('public')->url($settings->hero_image_path) : asset('images/simkopdes/hero.webp') }}');">
        <div class="container hero-grid">
            <p class="hero-eyebrow">{{ $settings->name }} @if($settings->provinceName())<span> · {{ $settings->provinceName() }}</span>@endif</p>
            <h1 id="hero-title">{{ $settings->hero_title ?: $portal['hero_title'] }}</h1>
            <p class="hero-tagline">{{ $settings->tagline }}</p>
            <div class="hero-actions">
                <a class="button lime" href="#tentang">Tentang Koperasi</a>
                <a class="button white" href="#jaringan">Jaringan Koperasi</a>
                <a class="button outline-light" href="https://simkopdes.go.id/" target="_blank" rel="noopener noreferrer">Keanggotaan di Simkopdes <x-app-icon name="arrow" /></a>
            </div>
        </div>
    </section>

    @php
        $networks = \App\Models\CooperativeNetwork::query()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->get()->toArray();
        $networkCount = count($networks);
    @endphp

    @if(count($settings->services ?? []))
        <section id="usaha" class="unit-grid-section container">
            <div class="section-heading-center">
                <h2>Unit Usaha & Layanan Koperasi</h2>
                <p>Kenali ragam layanan yang tersedia di {{ $settings->name }}.</p>
            </div>
            <div class="unit-grid">
                @foreach($settings->services as $service)
                    <article class="unit-card">
                        <div class="unit-card-top">
                            <span class="unit-icon"><x-app-icon name="document" /></span>
                            <span>Unit {{ $loop->iteration }}</span>
                        </div>
                        <h3>{{ $service['title'] }}</h3>
                        <p>{{ $service['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div id="jaringan">
        <div class="cta-band">
            <div class="container">
                <h2>Temukan Koperasi Desa di Sekitar Anda</h2>
                <p>Jelajahi jaringan koperasi yang terhubung{{ $settings->provinceName() ? ' di wilayah '.$settings->provinceName() : '' }}. Cari berdasarkan nama atau wilayah.</p>
                <div class="cta-features">
                    <div class="cta-feature">
                        <span class="cta-feature-icon"><x-app-icon name="search" /></span>
                        <h3>Pencarian Mudah</h3>
                        <p>Cari koperasi berdasarkan nama atau wilayah dengan cepat.</p>
                    </div>
                    <div class="cta-feature">
                        <span class="cta-feature-icon"><x-app-icon name="document" /></span>
                        <h3>Data Terverifikasi</h3>
                        <p>Profil dan kontak koperasi diverifikasi melalui Simkopdes.</p>
                    </div>
                    <div class="cta-feature">
                        <span class="cta-feature-icon"><x-app-icon name="pin" /></span>
                        <h3>Jaringan Terhubung</h3>
                        <p>Bagian dari jaringan koperasi desa/kelurahan se-Indonesia.</p>
                    </div>
                </div>
            </div>
        </div>

        <section class="profile-section" style="background: var(--soft);">
            <div class="container">
                <div class="section-heading">
                    <h2>Temukan koperasi,<br>kenali potensi di sekitarnya</h2>
                    <div class="network-search">
                        <label class="sr-only" for="network-search">Cari nama koperasi atau wilayah</label>
                        <x-app-icon name="search" />
                        <input id="network-search" type="search" placeholder="Cari nama koperasi atau wilayah…" data-network-search>
                        <p>Data Kopdes tiap provinsi se-Indonesia</p>
                    </div>
                </div>
                <p class="network-count" data-network-count aria-live="polite">{{ count($networks) }} koperasi</p>
                <div class="network-grid" id="network-track">
                    @foreach($networks as $network)
                        <article class="network-card" data-network-row><div class="network-title"><img src="{{ asset('images/simkopdes/logo.webp') }}" alt="" width="58" height="17"><h3>{{ $network['name'] }}</h3></div><p class="network-region"><x-app-icon name="pin" /> {{ $network['region'] }}</p><div class="network-details"><span>Informasi koperasi</span><p>Profil, potensi desa, dan informasi anggota tersedia melalui situs koperasi.</p></div>@if(!empty($network['url']))<a class="button" href="{{ $network['url'] }}" target="_blank" rel="noopener noreferrer">Lihat Situs <x-app-icon name="external" /><span class="sr-only"> {{ $network['name'] }} (tab baru)</span></a>@else<span class="unavailable">Website belum tersedia</span>@endif</article>
                    @endforeach
                    @if(!count($networks))<div class="empty-state"><h3>Jaringan wilayah belum ditambahkan</h3><p>Daftar Data Kopdes provinsi akan tersedia setelah ditambahkan lewat admin.</p></div>@endif
                </div>
                <p class="empty-state" data-network-empty hidden role="status">Koperasi tidak ditemukan. Coba nama atau wilayah lain.</p>
                <div class="network-pagination" data-network-pagination>
                    <button type="button" class="carousel-btn" data-network-prev aria-label="Halaman sebelumnya"><x-app-icon name="left" /></button>
                    <span class="network-page-label" data-network-page-label></span>
                    <button type="button" class="carousel-btn" data-network-next aria-label="Halaman berikutnya"><x-app-icon name="arrow" /></button>
                </div>
            </div>
        </section>
    </div>

    <section id="statistik" class="stats-band">
        <div class="container">
            <x-regional-statistics :summary="$summary" :cards="$statCards" />
            <a class="text-link" href="{{ route('data-kopdes') }}">Buka halaman data wilayah <x-app-icon name="arrow" /></a>
        </div>
    </section>

    @if($articles->isNotEmpty())
        <section id="berita" class="profile-section">
            <div class="container">
                <div class="section-heading"><h2>Kabar koperasi</h2><a class="text-link" href="{{ route('news.index') }}">Semua berita <x-app-icon name="arrow" /></a></div>
                <div class="news-grid">@foreach($articles as $article)<x-news-card :article="$article" />@endforeach</div>
            </div>
        </section>
    @endif

    <section id="tentang" class="profile-section">
        <div class="container about-section">
            <img src="{{ asset('images/simkopdes/about-illustration.svg') }}" alt="Ilustrasi ekonomi desa tumbuh bersama inovasi digital" width="1600" height="600">
            <div>
                <span class="about-eyebrow">Tentang Koperasi</span>
                <h2>Ekonomi desa tumbuh bersama inovasi digital</h2>
                <p>{{ $settings->about ?: $portal['about'] }}</p>
            </div>
        </div>
    </section>

    <section id="regulasi" class="profile-section" style="background: var(--soft);">
        <div class="container">
            <div class="section-lead">
                <span class="eyebrow">DASAR HUKUM</span>
                <h2>Dasar hukum & regulasi</h2>
                <p>Telusuri dokumen rujukan untuk memahami kelembagaan dan pelaksanaan program koperasi.</p>
            </div>
            <div class="regulation-filters" role="group" aria-label="Jenis regulasi">@foreach(collect($portal['regulations'])->pluck('category')->unique()->values() as $category)<button type="button" data-regulation-filter="{{ $category }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $category }}</button>@endforeach</div>
            <div class="regulation-grid">@foreach($portal['regulations'] as $regulation)<article class="regulation-card" data-regulation-category="{{ $regulation['category'] }}" @if($regulation['category'] !== 'Undang-Undang') hidden @endif><span class="document-icon"><x-app-icon name="document" /></span><div class="regulation-body"><h3>{{ $regulation['title'] }}</h3><p>{{ $regulation['description'] }}</p></div><span class="regulation-tag">{{ $regulation['category'] }}</span><a class="text-link" href="{{ $regulation['url'] }}" target="_blank" rel="noopener noreferrer">Buka dokumen <x-app-icon name="external" /></a></article>@endforeach</div>
            <a class="text-link regulation-source" href="https://jdih.kop.go.id/doc/peraturan_kdmp" target="_blank" rel="noopener noreferrer">Koleksi regulasi KDKMP di JDIH Kemenkop <x-app-icon name="external" /></a>
        </div>
    </section>

    <section id="manfaat" class="section container">
        <div class="section-heading">
            <h2>13 manfaat koperasi<br>untuk ekonomi desa</h2>
            <div class="carousel-nav">
                <button type="button" class="carousel-btn" data-carousel-prev="manfaat-track" aria-label="Sebelumnya"><x-app-icon name="left" /></button>
                <button type="button" class="carousel-btn" data-carousel-next="manfaat-track" aria-label="Berikutnya"><x-app-icon name="arrow" /></button>
            </div>
        </div>
        <div class="benefit-grid" id="manfaat-track" data-carousel>@foreach($portal['benefits'] as $benefit)<article class="benefit-card"><img src="{{ asset('images/simkopdes/'.$benefit['image']) }}" alt="" loading="lazy" width="400" height="600"><div><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} / 13</span><h3>{{ $benefit['title'] }}</h3></div></article>@endforeach</div>
        <p class="source-note">Arah manfaat program berdasarkan <a href="{{ $portal['benefits_source'] }}" target="_blank" rel="noopener noreferrer">petunjuk pelaksanaan pembentukan KDKMP ↗</a>.</p>
    </section>

    <section id="aplikasi" class="app-section"><div class="container"><div class="app-panel"><div class="app-copy"><h2>Informasi koperasi<br>dalam genggaman</h2><p>Gunakan Simkopdes Mobile untuk mengakses informasi keanggotaan dan layanan digital koperasi.</p><div class="app-badges"><a href="https://apps.apple.com/id/app/kdmp-mobile/id6749216676" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/simkopdes/app-store.svg') }}" alt="Unduh di App Store" width="150" height="45" loading="lazy"></a><a href="https://play.google.com/store/apps/details?id=id.kop.merahputih.kdmp_mobile_app" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/simkopdes/play-store.svg') }}" alt="Unduh di Google Play" width="150" height="45" loading="lazy"></a></div></div><div class="app-visual"><img class="podium" src="{{ asset('images/simkopdes/podium.webp') }}" alt="" loading="lazy"><img class="phone-right" src="{{ asset('images/simkopdes/phone-right.webp') }}" alt="Tampilan profil di Simkopdes Mobile" loading="lazy"><img class="phone-left" src="{{ asset('images/simkopdes/phone-left.webp') }}" alt="Tampilan beranda Simkopdes Mobile" loading="lazy"></div></div></div></section>
</x-layout>

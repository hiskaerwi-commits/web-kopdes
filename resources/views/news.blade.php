<x-layout :settings="$settings" title="Berita & Kegiatan">
    <section class="page-banner"><div class="container"><p class="eyebrow">KABAR DARI KOPERASI</p><h1>Berita & <em>kegiatan.</em></h1><p>Pengumuman, kegiatan, dan cerita dari koperasi.</p></div></section>
    <section class="container section">
        <form action="{{ route('news.index') }}" method="get" class="search-form"><label for="news-search">Cari berita</label><div><input class="search-input" id="news-search" name="q" type="search" value="{{ $search }}" placeholder="Ketik judul berita…"><button class="button" type="submit">Cari <span aria-hidden="true">↗</span></button>@if($search)<a href="{{ route('news.index') }}" class="text-link">Reset</a>@endif</div></form>
        <div class="news-grid">
            @forelse($articles as $article)
                <x-news-card :article="$article" />
            @empty
                <div class="empty-state"><h2>{{ $search ? 'Berita tidak ditemukan' : 'Belum ada berita' }}</h2><p>{{ $search ? 'Coba kata kunci lain untuk mencari berita.' : 'Kegiatan dan pengumuman koperasi akan ditampilkan di sini.' }}</p></div>
            @endforelse
        </div>
        <div class="pagination">{{ $articles->links() }}</div>
    </section>
</x-layout>

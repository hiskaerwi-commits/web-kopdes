<x-layout :settings="$settings" :title="$article->title" :description="$article->excerpt">
    <article class="container section article">
        <a class="text-link" href="{{ route('news.index') }}">← Kembali ke berita</a>
        <div class="news-meta"><span>{{ $article->category }}</span><time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time></div>
        <h1>{{ $article->title }}</h1>
        <p class="article-excerpt">{{ $article->excerpt }}</p>
        @if($article->image_path)
            <img class="article-image" src="{{ Storage::disk('public')->url($article->image_path) }}" alt="{{ $article->title }}">
        @endif
        <div class="article-body">{{ $article->body }}</div>
    </article>
</x-layout>

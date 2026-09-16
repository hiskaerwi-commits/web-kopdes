<?php

use App\Models\Article;
use App\Models\SiteSetting;
use App\Services\SimkopdesData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (SimkopdesData $data) {
    $settings = SiteSetting::current();
    $summary = $data->summary($settings->scopeCode());

    return view('home', [
    'settings' => $settings,
    'summary' => $summary,
    'statCards' => $data->cards($summary),
    'articles' => Article::published()->latest('published_at')->limit(3)->get(),
    'portal' => config('portal'),
    ]);
})->name('home');

Route::get('/data-kopdes', function (Request $request, SimkopdesData $data) {
    $settings = SiteSetting::current();
    // A configured website's scope always takes precedence over public query parameters.
    $code = $settings->scopeCode();
    if (! $code) {
        $filters = $request->validate(['province' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_column(SimkopdesData::provinces(), 'code'))]]);
        $code = $filters['province'] ?? null;
    }
    $summary = $data->summary($code);
    $provinceDetail = ($code && ! str_contains($code, '.')) ? $data->provinceDetail($code) : null;

    return view('data-kopdes', ['settings' => $settings, 'summary' => $summary, 'statCards' => $data->cards($summary), 'provinces' => SimkopdesData::provinces(), 'provinceDetail' => $provinceDetail]);
})->name('data-kopdes');

Route::get('/berita', function (Request $request) {
    $search = trim((string) $request->query('q', ''));

    return view('news', [
        'settings' => SiteSetting::current(),
        'search' => $search,
        'articles' => Article::published()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->latest('published_at')->paginate(9)->withQueryString(),
    ]);
})->name('news.index');

Route::get('/berita/{slug}', function (string $slug) {
    return view('article', [
        'settings' => SiteSetting::current(),
        'article' => Article::published()->where('slug', $slug)->firstOrFail(),
    ]);
})->name('news.show');

Route::get('/sitemap.xml', function () {
    $urls = collect([
        ['loc' => route('home'), 'lastmod' => SiteSetting::current()->updated_at, 'priority' => '1.0'],
        ['loc' => route('data-kopdes'), 'priority' => '0.8'],
        ['loc' => route('news.index'), 'priority' => '0.6'],
    ])->merge(
        Article::published()->latest('published_at')->get(['slug', 'published_at', 'updated_at'])
            ->map(fn (Article $article) => [
                'loc' => route('news.show', $article->slug),
                'lastmod' => $article->updated_at,
                'priority' => '0.5',
            ])
    );

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin',
        'Sitemap: '.route('sitemap'),
    ];

    return response(implode("\n", $lines))->header('Content-Type', 'text/plain');
})->name('robots');

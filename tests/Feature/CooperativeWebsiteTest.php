<?php

namespace Tests\Feature;

use App\Filament\Pages\WebsiteSettings;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Models\Article;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CooperativeWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_work_and_guests_cannot_open_admin_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('Koperasi Desa Merah Putih');
        $this->get('/berita')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/website-settings')->assertRedirect('/admin/login');
        $this->get('/admin/articles/create')->assertRedirect('/admin/login');
    }

    public function test_drafts_and_scheduled_news_are_not_public(): void
    {
        foreach (['draft', 'future', 'public'] as $status) {
            Article::create([
                'title' => 'Artikel '.$status, 'slug' => $status, 'category' => 'Kegiatan',
                'excerpt' => 'Ringkasan', 'body' => 'Isi berita', 'is_published' => $status !== 'draft',
                'published_at' => $status === 'future' ? now()->addDay() : now()->subDay(),
            ]);
        }
        foreach (['/', '/berita'] as $url) {
            $this->get($url)->assertOk()->assertSee('Artikel public')->assertDontSee('Artikel draft')->assertDontSee('Artikel future');
        }
        $this->get('/berita/draft')->assertNotFound();
        $this->get('/berita/future')->assertNotFound();
        $this->get('/berita/public')->assertOk();
        $this->get('/berita?q=missing')->assertOk()->assertDontSee('Artikel public')->assertSee('Berita tidak ditemukan');
        $this->get('/berita?q=public')->assertOk()->assertSee('Artikel public');
    }

    public function test_admin_can_save_regional_settings_services_and_networks(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(WebsiteSettings::class)
            ->set('data.name', 'KDMP Contoh')->set('data.province', 'Jawa Barat')->set('data.village', 'Desa Contoh')
            ->set('data.email', 'koperasi@example.com')
            ->set('data.services', [['title' => 'Gerai Sembako', 'description' => 'Kebutuhan sehari-hari.']])
            ->set('data.networks', [['name' => 'Koperasi Mitra', 'region' => 'Bandung', 'url' => 'https://example.org']])
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Jawa Barat', SiteSetting::current()->province);
        $this->get('/')->assertOk()->assertSee('KDMP Contoh')->assertSee('Jawa Barat')->assertSee('Gerai Sembako')->assertSee('Koperasi Mitra')->assertSee('mailto:koperasi@example.com', false);
        Livewire::test(WebsiteSettings::class)->set('data.services', [])->set('data.networks', [])->call('save')->assertHasNoErrors();
        $this->get('/')->assertDontSee('Gerai Sembako')->assertDontSee('Koperasi Mitra');
    }

    public function test_settings_validate_contact_information_and_network_urls(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(WebsiteSettings::class)
            ->set('data.email', 'invalid')->set('data.phone', 'abc')
            ->set('data.networks', [['name' => 'Invalid', 'region' => 'Test', 'url' => 'javascript:alert(1)']])
            ->call('save')->assertHasErrors(['data.email', 'data.phone', 'data.networks.0.url']);
    }

    public function test_admin_can_publish_news_and_content_is_escaped(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(CreateArticle::class)->set('data.title', 'Rapat anggota')->set('data.slug', 'rapat-anggota')
            ->set('data.category', 'Kegiatan')->set('data.excerpt', 'Ringkasan rapat')
            ->set('data.body', '<script>alert(1)</script>')->set('data.published_at', now()->subHour()->format('Y-m-d H:i:s'))
            ->set('data.is_published', true)->call('create')->assertHasNoErrors();
        $this->get('/berita/rapat-anggota')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}

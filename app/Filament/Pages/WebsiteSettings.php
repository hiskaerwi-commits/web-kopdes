<?php

namespace App\Filament\Pages;

use App\Jobs\SyncProvinceStatistics;
use App\Models\SiteSetting;
use App\Models\Wilayah;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WebsiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Pengaturan Website';

    protected string $view = 'filament.pages.website-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make('Pengaturan')->persistTabInQueryString()->columnSpanFull()->tabs([

                Tabs\Tab::make('Identitas & Wilayah')->icon(Heroicon::OutlinedIdentification)->columns(2)->schema([
                    TextInput::make('name')->label('Nama website / koperasi')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('tagline')->label('Slogan')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('about')->label('Profil koperasi')->rows(5)->maxLength(10000)->columnSpanFull(),
                    Select::make('province_code')->label('Provinsi')->options(fn () => Wilayah::provinces())->searchable()->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('regency_code', null);
                            $set('district_code', null);
                            $set('village_code', null);
                        }),
                    Select::make('regency_code')->label('Kabupaten/Kota')->options(fn (Get $get) => Wilayah::children($get('province_code')))->searchable()->live()
                        ->disabled(fn (Get $get) => ! $get('province_code'))
                        ->dehydrated()
                        ->afterStateUpdated(function (Set $set) {
                            $set('district_code', null);
                            $set('village_code', null);
                        }),
                    Select::make('district_code')->label('Kecamatan')->options(fn (Get $get) => Wilayah::children($get('regency_code')))->searchable()->live()
                        ->disabled(fn (Get $get) => ! $get('regency_code'))
                        ->dehydrated()
                        ->afterStateUpdated(fn (Set $set) => $set('village_code', null)),
                    Select::make('village_code')->label('Desa/Kelurahan')->options(fn (Get $get) => Wilayah::children($get('district_code')))->searchable()
                        ->disabled(fn (Get $get) => ! $get('district_code'))
                        ->dehydrated(),
                ]),

                Tabs\Tab::make('Media & Branding')->icon(Heroicon::OutlinedPhoto)->schema([
                    FileUpload::make('logo_path')->label('Logo koperasi')->helperText('Tampil di pojok kiri atas header semua halaman.')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])->disk('public')->directory('branding')->visibility('public')->maxSize(2048),
                    FileUpload::make('favicon_path')->label('Favicon / ikon tab browser')->helperText('Gambar persegi, minimal 512×512px. Kosongkan untuk memakai ikon bawaan Simkopdes.')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/svg+xml'])->disk('public')->directory('branding')->visibility('public')->maxSize(512),
                    Textarea::make('hero_title')->label('Judul banner utama (hero)')->placeholder(config('portal.hero_title'))->rows(2)->maxLength(180)->columnSpanFull(),
                    FileUpload::make('hero_image_path')->label('Foto latar banner utama (hero)')->helperText('Jadi background penuh di section hero halaman depan. Kosongkan untuk memakai foto referensi Simkopdes.')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('branding')->visibility('public')->maxSize(5120)->columnSpanFull(),
                ]),

                Tabs\Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    TextInput::make('meta_title')->label('Meta title')->helperText('Judul yang tampil di tab browser & hasil pencarian Google. Kosongkan untuk memakai Nama website.')->maxLength(60)->columnSpanFull(),
                    Textarea::make('meta_description')->label('Meta description')->helperText('Ringkasan singkat yang tampil di bawah judul pada hasil pencarian Google. Idealnya 120–160 karakter.')->rows(3)->maxLength(160)->columnSpanFull(),
                    TextInput::make('meta_keywords')->label('Meta keywords')->helperText('Kata kunci dipisah koma, contoh: koperasi desa, KDMP, Banten.')->maxLength(255)->columnSpanFull(),
                    FileUpload::make('og_image_path')->label('Gambar bagikan (Open Graph)')->helperText('Gambar yang muncul saat link website dibagikan di WhatsApp/Facebook/Twitter. Ukuran ideal 1200×630px. Kosongkan untuk memakai foto banner hero.')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('branding')->visibility('public')->maxSize(2048)->columnSpanFull(),
                ]),

                Tabs\Tab::make('Kontak')->icon(Heroicon::OutlinedPhone)->columns(2)->schema([
                    Textarea::make('address')->label('Alamat')->maxLength(2000)->columnSpanFull(),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),
                    TextInput::make('phone')->label('Telepon')->tel()->regex('/^[+0-9 ()-]+$/')->maxLength(30),
                    TextInput::make('hours')->label('Jam layanan')->maxLength(255)->columnSpanFull(),
                ]),

                Tabs\Tab::make('Unit Usaha')->icon(Heroicon::OutlinedBuildingStorefront)->schema([
                    Repeater::make('services')->label('Daftar unit usaha')->hiddenLabel()
                        ->schema([
                            TextInput::make('title')->label('Nama unit')->required()->maxLength(120),
                            Textarea::make('description')->label('Keterangan')->required()->maxLength(1000),
                        ])->defaultItems(0)->maxItems(30)->addActionLabel('Tambah unit usaha'),
                ]),

            ]),
        ]);
    }

    public function save(): void
    {
        $setting = SiteSetting::current();
        $previousProvince = $setting->province_code;
        $setting->update($this->form->getState());
        Notification::make()->title('Pengaturan berhasil disimpan')->success()->send();

        if ($setting->province_code && $setting->province_code !== $previousProvince) {
            dispatch(new SyncProvinceStatistics($setting->province_code))->afterResponse();
        }
    }
}

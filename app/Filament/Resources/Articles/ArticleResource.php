<?php

namespace App\Filament\Resources\Articles;

use App\Models\Article;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $modelLabel = 'Berita';

    protected static ?string $pluralModelLabel = 'Berita';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Judul')->required()->maxLength(255),
            TextInput::make('slug')->label('Slug URL')->helperText('Contoh: rapat-anggota-tahunan')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('category')->label('Kategori')->default('Kegiatan')->required()->maxLength(80),
            DateTimePicker::make('published_at')->label('Tanggal terbit')->default(now())->required(),
            Textarea::make('excerpt')->label('Ringkasan')->required()->maxLength(500)->columnSpanFull(),
            Textarea::make('body')->label('Isi berita')->rows(12)->required()->maxLength(100000)->columnSpanFull(),
            FileUpload::make('image_path')->label('Foto')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('news')->visibility('public')->maxSize(3072),
            Toggle::make('is_published')->label('Publikasikan')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Judul')->searchable()->limit(60),
            TextColumn::make('category')->label('Kategori')->badge(),
            TextColumn::make('published_at')->label('Terbit')->dateTime('d M Y H:i')->sortable(),
            IconColumn::make('is_published')->label('Publikasi aktif')->boolean(),
        ])->defaultSort('published_at', 'desc')->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListArticles::route('/'), 'create' => Pages\CreateArticle::route('/create'), 'edit' => Pages\EditArticle::route('/{record}/edit')];
    }
}

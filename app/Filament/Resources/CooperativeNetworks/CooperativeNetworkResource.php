<?php

namespace App\Filament\Resources\CooperativeNetworks;

use App\Models\CooperativeNetwork;
use App\Models\SiteSetting;
use App\Models\Wilayah;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CooperativeNetworkResource extends Resource
{
    protected static ?string $model = CooperativeNetwork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $modelLabel = 'Jaringan Koperasi';

    protected static ?string $pluralModelLabel = 'Jaringan Koperasi';

    protected static ?string $navigationLabel = 'Jaringan Koperasi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama koperasi')->required()->maxLength(150),
            TextInput::make('region')->label('Desa / kabupaten / provinsi')->required()->maxLength(255),
            Select::make('region_code')->label('Kode wilayah jaringan')->searchable()
                ->getSearchResultsUsing(fn (string $search) => Wilayah::query()->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', $search.'%'))->limit(50)->get()->mapWithKeys(fn ($row) => [$row->code => $row->name.' ('.$row->code.')'])->all())
                ->getOptionLabelUsing(fn ($value) => ($row = Wilayah::find($value)) ? $row->name.' ('.$row->code.')' : null)
                ->default(fn () => SiteSetting::current()->scopeCode())->exists('wilayah', 'code')
                ->helperText('Website wilayah hanya menampilkan jaringan dengan kode ini atau turunannya. Tanpa kode hanya tampil di website nasional.'),
            TextInput::make('url')->label('Website')->url()->rules(['starts_with:https://,http://'])->maxLength(2048),
            TextInput::make('sort_order')->label('Urutan tampil')->numeric()->default(0)->required(),
            Toggle::make('is_active')->label('Tampilkan di halaman depan')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama koperasi')->searchable()->limit(60),
            TextColumn::make('region')->label('Wilayah')->searchable(),
            TextColumn::make('url')->label('Website')->limit(40)->url(fn (CooperativeNetwork $record) => $record->url, shouldOpenInNewTab: true),
            TextColumn::make('sort_order')->label('Urutan')->sortable(),
            IconColumn::make('is_active')->label('Tampil')->boolean(),
        ])->defaultSort('sort_order')->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCooperativeNetworks::route('/'),
            'create' => Pages\CreateCooperativeNetwork::route('/create'),
            'edit' => Pages\EditCooperativeNetwork::route('/{record}/edit'),
        ];
    }
}

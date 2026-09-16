<?php

namespace App\Filament\Resources\CooperativeNetworks\Pages;

use App\Filament\Resources\CooperativeNetworks\CooperativeNetworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCooperativeNetworks extends ListRecords
{
    protected static string $resource = CooperativeNetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah jaringan')];
    }
}

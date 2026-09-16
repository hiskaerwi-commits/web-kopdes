<?php

namespace App\Filament\Resources\CooperativeNetworks\Pages;

use App\Filament\Resources\CooperativeNetworks\CooperativeNetworkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCooperativeNetwork extends EditRecord
{
    protected static string $resource = CooperativeNetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

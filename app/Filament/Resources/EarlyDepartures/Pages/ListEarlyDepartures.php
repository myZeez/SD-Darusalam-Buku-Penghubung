<?php

namespace App\Filament\Resources\EarlyDepartures\Pages;

use App\Filament\Resources\EarlyDepartures\EarlyDepartureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEarlyDepartures extends ListRecords
{
    protected static string $resource = EarlyDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Catat Pulang Cepat')];
    }
}

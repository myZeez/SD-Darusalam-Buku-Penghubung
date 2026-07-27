<?php

namespace App\Filament\Resources\EarlyDepartures\Pages;

use App\Filament\Resources\EarlyDepartures\EarlyDepartureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEarlyDeparture extends ViewRecord
{
    protected static string $resource = EarlyDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->visible(fn (): bool => EarlyDepartureResource::canEdit($this->record))];
    }
}

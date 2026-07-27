<?php

namespace App\Filament\Resources\EarlyDepartures\Pages;

use App\Filament\Resources\EarlyDepartures\EarlyDepartureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEarlyDeparture extends EditRecord
{
    protected static string $resource = EarlyDepartureResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn (): bool => EarlyDepartureResource::canDelete($this->record))];
    }
}

<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\Schedules\Tables\SchedulesTable;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchedule extends ViewRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SchedulesTable::parentResponseAction(),
            EditAction::make()
                ->visible(fn (): bool => ScheduleResource::canEdit($this->record)),
        ];
    }
}

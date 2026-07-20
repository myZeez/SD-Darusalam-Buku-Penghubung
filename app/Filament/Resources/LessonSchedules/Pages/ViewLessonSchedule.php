<?php

namespace App\Filament\Resources\LessonSchedules\Pages;

use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonSchedule extends ViewRecord
{
    protected static string $resource = LessonScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->visible(fn (): bool => LessonScheduleResource::canEdit($this->record))];
    }
}

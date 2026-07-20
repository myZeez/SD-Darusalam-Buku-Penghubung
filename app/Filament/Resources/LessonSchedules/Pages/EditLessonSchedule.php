<?php

namespace App\Filament\Resources\LessonSchedules\Pages;

use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonSchedule extends EditRecord
{
    protected static string $resource = LessonScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (): bool => LessonScheduleResource::canDelete($this->record)),
        ];
    }
}

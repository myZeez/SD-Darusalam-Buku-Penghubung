<?php

namespace App\Filament\Resources\LessonPeriods\Pages;

use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonPeriod extends EditRecord
{
    protected static string $resource = LessonPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (): bool => LessonPeriodResource::canDelete($this->record)),
        ];
    }
}

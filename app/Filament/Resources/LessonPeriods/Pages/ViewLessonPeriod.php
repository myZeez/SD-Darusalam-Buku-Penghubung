<?php

namespace App\Filament\Resources\LessonPeriods\Pages;

use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonPeriod extends ViewRecord
{
    protected static string $resource = LessonPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->visible(fn (): bool => LessonPeriodResource::canEdit($this->record))];
    }
}

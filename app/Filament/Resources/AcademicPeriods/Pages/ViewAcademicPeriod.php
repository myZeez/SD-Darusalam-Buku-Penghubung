<?php

namespace App\Filament\Resources\AcademicPeriods\Pages;

use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicPeriod extends ViewRecord
{
    protected static string $resource = AcademicPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => AcademicPeriodResource::canEdit($this->record)),
        ];
    }
}

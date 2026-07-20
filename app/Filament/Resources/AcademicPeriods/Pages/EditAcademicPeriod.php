<?php

namespace App\Filament\Resources\AcademicPeriods\Pages;

use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAcademicPeriod extends EditRecord
{
    protected static string $resource = AcademicPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (): bool => AcademicPeriodResource::canDelete($this->record)),
        ];
    }
}

<?php

namespace App\Filament\Resources\SchoolSettings\Pages;

use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchoolSetting extends ViewRecord
{
    protected static string $resource = SchoolSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => SchoolSettingResource::canEdit($this->record)),
        ];
    }
}

<?php

namespace App\Filament\Resources\SchoolSettings\Pages;

use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolSetting extends EditRecord
{
    protected static string $resource = SchoolSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => SchoolSettingResource::canDelete($this->record)),
        ];
    }
}

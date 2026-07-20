<?php

namespace App\Filament\Resources\SchoolSettings\Pages;

use App\Filament\Resources\SchoolSettings\SchoolSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolSettings extends ListRecords
{
    protected static string $resource = SchoolSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => SchoolSettingResource::canCreate()),
        ];
    }
}

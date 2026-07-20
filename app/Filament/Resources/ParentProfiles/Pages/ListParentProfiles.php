<?php

namespace App\Filament\Resources\ParentProfiles\Pages;

use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParentProfiles extends ListRecords
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => ParentProfileResource::canCreate()),
        ];
    }
}

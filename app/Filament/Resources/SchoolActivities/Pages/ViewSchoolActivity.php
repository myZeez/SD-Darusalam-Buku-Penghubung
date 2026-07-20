<?php

namespace App\Filament\Resources\SchoolActivities\Pages;

use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchoolActivity extends ViewRecord
{
    protected static string $resource = SchoolActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => SchoolActivityResource::canEdit($this->record)),
        ];
    }
}

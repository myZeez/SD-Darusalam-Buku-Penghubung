<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHomeActivity extends ViewRecord
{
    protected static string $resource = HomeActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => HomeActivityResource::canEdit($this->record)),
        ];
    }
}

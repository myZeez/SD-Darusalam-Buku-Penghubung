<?php

namespace App\Filament\Resources\ParentProfiles\Pages;

use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParentProfile extends ViewRecord
{
    protected static string $resource = ParentProfileResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('orang_tua') ? 'Profil Orang Tua' : 'Detail Orang Tua';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => ParentProfileResource::canEdit($this->record)),
        ];
    }
}

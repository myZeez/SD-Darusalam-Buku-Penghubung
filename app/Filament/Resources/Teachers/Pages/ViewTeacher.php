<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacher extends ViewRecord
{
    protected static string $resource = TeacherResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Profil Saya' : 'Detail Guru';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => TeacherResource::canEdit($this->record)),
        ];
    }
}

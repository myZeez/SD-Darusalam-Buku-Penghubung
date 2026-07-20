<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function getTitle(): string
    {
        $user = auth()->user();

        return match (true) {
            $user?->hasRole('orang_tua') => 'Profil Anak',
            $user?->hasRole('siswa') => 'Profil Saya',
            default => 'Detail Siswa',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => StudentResource::canEdit($this->record)),
            Action::make('editParent')
                ->label('Ubah Data Orang Tua')
                ->icon('heroicon-o-user-group')
                ->url(fn (): string => ParentProfileResource::getUrl('edit', ['record' => $this->record->parent_id]))
                ->visible(fn (): bool => (auth()->user()?->can('manage parents') ?? false) && filled($this->record->parent_id)),
        ];
    }
}

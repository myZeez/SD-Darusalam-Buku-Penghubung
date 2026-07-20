<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeActivities extends ListRecords
{
    protected static string $resource = HomeActivityResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->hasRole('orang_tua')
            ? 'Centang aktivitas rumah yang telah dilakukan, lalu tambahkan catatan bila diperlukan.'
            : 'Buat daftar aktivitas rumah yang perlu dilakukan siswa. Orang tua akan melengkapi checklist-nya.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => HomeActivityResource::canCreate()),
        ];
    }
}

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
        return 'Catatan orang tua tentang ibadah, belajar, tugas, istirahat, dan kebiasaan siswa di rumah.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => HomeActivityResource::canCreate()),
        ];
    }
}

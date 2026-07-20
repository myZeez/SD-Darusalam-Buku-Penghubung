<?php

namespace App\Filament\Resources\SchoolActivities\Pages;

use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolActivities extends ListRecords
{
    protected static string $resource = SchoolActivityResource::class;

    public function getSubheading(): ?string
    {
        return 'Catatan harian guru tentang kehadiran, pembelajaran, kebiasaan, dan perkembangan setiap siswa.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => SchoolActivityResource::canCreate()),
        ];
    }
}

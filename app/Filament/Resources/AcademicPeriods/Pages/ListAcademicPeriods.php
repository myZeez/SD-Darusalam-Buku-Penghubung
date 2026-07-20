<?php

namespace App\Filament\Resources\AcademicPeriods\Pages;

use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademicPeriods extends ListRecords
{
    protected static string $resource = AcademicPeriodResource::class;

    public function getTitle(): string
    {
        return 'Tahun Ajaran dan Semester';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola periode yang menjadi acuan kelas, penugasan guru, dan jadwal pelajaran.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Periode')->icon('gmdi-add-circle-o'),
        ];
    }
}

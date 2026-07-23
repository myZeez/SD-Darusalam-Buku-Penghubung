<?php

namespace App\Filament\Resources\StudentArrivals\Pages;

use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentArrivals extends ListRecords
{
    protected static string $resource = StudentArrivalResource::class;

    public function getTitle(): string
    {
        return 'Kedatangan Siswa';
    }

    public function getSubheading(): ?string
    {
        return 'Pantau waktu datang, kelas, dan status keterlambatan siswa berdasarkan kelas.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

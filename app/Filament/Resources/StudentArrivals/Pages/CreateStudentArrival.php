<?php

namespace App\Filament\Resources\StudentArrivals\Pages;

use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentArrival extends CreateRecord
{
    protected static string $resource = StudentArrivalResource::class;

    public function getTitle(): string
    {
        return 'Catat Kedatangan Siswa';
    }

    public function getSubheading(): ?string
    {
        return 'Cari siswa berdasarkan nama atau NIS. Kelas dan status terlambat akan terisi otomatis.';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kedatangan siswa berhasil dicatat';
    }
}

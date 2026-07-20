<?php

namespace App\Filament\Resources\StudentArrivals\Pages;

use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentArrivals extends ListRecords
{
    protected static string $resource = StudentArrivalResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('loket')
            ? 'Loket Kedatangan'
            : 'Kedatangan Siswa';
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->hasRole('loket')
            ? 'Catat siswa yang tiba hari ini. Kelas dan status keterlambatan ditentukan otomatis oleh sistem.'
            : 'Pantau waktu datang, kelas, dan status keterlambatan siswa.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Kedatangan')
                ->icon('gmdi-add-circle-o')
                ->visible(fn (): bool => StudentArrivalResource::canCreate()),
        ];
    }
}

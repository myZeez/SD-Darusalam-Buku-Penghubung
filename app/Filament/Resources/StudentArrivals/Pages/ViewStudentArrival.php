<?php

namespace App\Filament\Resources\StudentArrivals\Pages;

use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentArrival extends ViewRecord
{
    protected static string $resource = StudentArrivalResource::class;

    public function getTitle(): string
    {
        return 'Detail Kedatangan Siswa';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => StudentArrivalResource::canEdit($this->record)),
        ];
    }
}

<?php

namespace App\Filament\Resources\StudentArrivals\Pages;

use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentArrival extends EditRecord
{
    protected static string $resource = StudentArrivalResource::class;

    public function getTitle(): string
    {
        return 'Ubah Catatan Kedatangan';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Catatan kedatangan berhasil diperbarui';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => StudentArrivalResource::canDelete($this->record)),
        ];
    }
}

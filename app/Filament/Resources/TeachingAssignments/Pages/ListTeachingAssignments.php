<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachingAssignments extends ListRecords
{
    protected static string $resource = TeachingAssignmentResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->hasRole('guru')
            ? 'Daftar mata pelajaran untuk kelas yang Anda ampu sebagai guru utama atau pendamping.'
            : 'Tugaskan mata pelajaran kepada guru utama pada setiap kelas.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Penugasan')
                ->icon('gmdi-add-circle-o')
                ->visible(fn (): bool => TeachingAssignmentResource::canCreate()),
        ];
    }
}

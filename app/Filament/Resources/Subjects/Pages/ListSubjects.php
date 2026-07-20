<?php

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;

    public function getSubheading(): ?string
    {
        return 'Kelola daftar mata pelajaran yang dapat diberikan kepada Guru dan kelas.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Mata Pelajaran')->icon('gmdi-add-circle-o')];
    }
}

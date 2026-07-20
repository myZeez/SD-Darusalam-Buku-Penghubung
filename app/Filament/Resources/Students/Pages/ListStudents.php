<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    public function getTitle(): string
    {
        $user = auth()->user();

        return match (true) {
            $user?->hasRole('orang_tua') => 'Anak Saya',
            $user?->hasRole('siswa') => 'Profil Saya',
            $user?->hasRole('guru') => 'Siswa Kelas Saya',
            default => 'Siswa',
        };
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();

        return match (true) {
            $user?->hasRole('orang_tua') => 'Ringkasan anak, kelas, dan wali kelas yang terhubung dengan akun keluarga Anda.',
            $user?->hasRole('siswa') => 'Ringkasan identitas, kelas, dan wali kelas Anda.',
            $user?->hasRole('guru') => 'Kelola siswa pada kelas wali atau kelas pendamping yang Anda tangani.',
            default => null,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => StudentResource::canCreate()),
        ];
    }
}

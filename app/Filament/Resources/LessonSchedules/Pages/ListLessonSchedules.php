<?php

namespace App\Filament\Resources\LessonSchedules\Pages;

use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonSchedules extends ListRecords
{
    protected static string $resource = LessonScheduleResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Jadwal Mengajar' : 'Jadwal Pelajaran';
    }

    public function getSubheading(): ?string
    {
        return match (true) {
            auth()->user()?->hasRole('guru') => 'Menampilkan seluruh jadwal pada kelas yang Anda ampu sebagai guru utama atau pendamping.',
            auth()->user()?->hasRole('orang_tua') => 'Jadwal pelajaran untuk kelas anak yang terhubung dengan akun keluarga.',
            default => 'Susun jadwal mingguan. Sistem mencegah benturan Guru, kelas, dan ruangan.',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Jadwal')
                ->icon('gmdi-add-circle-o')
                ->visible(fn (): bool => LessonScheduleResource::canCreate()),
        ];
    }
}

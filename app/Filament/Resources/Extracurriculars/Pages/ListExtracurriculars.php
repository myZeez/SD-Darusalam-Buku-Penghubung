<?php

namespace App\Filament\Resources\Extracurriculars\Pages;

use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListExtracurriculars extends ListRecords
{
    protected static string $resource = ExtracurricularResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAdmin()
            ? 'Kelola kegiatan, pembina, peserta, sesi, dokumentasi, dan penilaian ekstrakurikuler.'
            : 'Lihat ekstrakurikuler yang diikuti anak, jadwal rutin, dan ringkasan kehadirannya.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Ekstrakurikuler')
                ->visible(fn (): bool => ExtracurricularResource::canCreate()),
        ];
    }
}

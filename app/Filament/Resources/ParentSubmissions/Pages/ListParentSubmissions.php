<?php

namespace App\Filament\Resources\ParentSubmissions\Pages;

use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParentSubmissions extends ListRecords
{
    protected static string $resource = ParentSubmissionResource::class;

    public function getTitle(): string
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return 'Monitoring Izin dan Sakit';
        }

        return $user?->hasRole('guru') ? 'Laporan Orang Tua' : 'Informasi Kehadiran Anak';
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return 'Pantau pengajuan orang tua. Persetujuan atau penolakan tetap diproses oleh guru kelas.';
        }

        return $user?->hasRole('guru')
            ? 'Tinjau laporan sakit atau izin dari orang tua siswa di kelas Anda.'
            : 'Kirim informasi sakit atau izin. Kelas dan guru diambil otomatis dari anak yang dipilih.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Informasi')
                ->icon('gmdi-add-circle-o')
                ->visible(fn (): bool => ParentSubmissionResource::canCreate()),
        ];
    }
}

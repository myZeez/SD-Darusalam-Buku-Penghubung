<?php

namespace App\Filament\Resources\PasswordResetRequests\Pages;

use App\Filament\Resources\PasswordResetRequests\PasswordResetRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListPasswordResetRequests extends ListRecords
{
    protected static string $resource = PasswordResetRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Proses permintaan akun siswa atau orang tua, lalu password sementara dikirim ke wali kelas.';
    }
}

<?php

namespace App\Filament\Resources\UserNotifications\Pages;

use App\Filament\Resources\UserNotifications\UserNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserNotifications extends ListRecords
{
    protected static string $resource = UserNotificationResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Notifikasi Guru' : 'Notifikasi';
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->hasRole('guru')
            ? 'Pesan masuk dari sistem dan orang tua, serta riwayat notifikasi yang Anda kirim.'
            : 'Informasi terbaru yang ditujukan kepada akun Anda.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Kirim Notifikasi')
                ->visible(fn (): bool => UserNotificationResource::canCreate()),
        ];
    }
}

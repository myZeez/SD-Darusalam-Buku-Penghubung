<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pengguna')
                    ->description('Informasi utama dan hak akses pengguna.')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label('Foto Profil')
                            ->circular()
                            ->imageSize(96),
                        TextEntry::make('name')
                            ->label('Nama Lengkap'),
                        TextEntry::make('email')
                            ->label('Alamat Email')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label('Nomor Telepon')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('job_title')
                            ->label('Jabatan')
                            ->placeholder('Tidak ada jabatan khusus'),
                        TextEntry::make('roles.name')
                            ->label('Peran')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => User::roleLabel($state)),
                        TextEntry::make('status')
                            ->label('Status Akun')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                default => $state,
                            }),
                    ]),
                Section::make('Informasi Akun')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columns([
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('email_verified_at')
                            ->label('Verifikasi Email')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('Belum diverifikasi'),
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ]),
            ]);
    }
}

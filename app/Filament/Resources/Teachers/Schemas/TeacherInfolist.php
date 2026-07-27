<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TeacherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil Wali Kelas')
                    ->description('Identitas dan kontak wali kelas yang terdaftar.')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        ImageEntry::make('user.avatar')
                            ->label('Foto Profil')
                            ->imageSize(120)
                            ->circular(),
                        TextEntry::make('user.name')
                            ->label('Nama Wali Kelas'),
                        TextEntry::make('class_duty_summary')
                            ->label('Wali Kelas')
                            ->columnSpanFull(),
                        TextEntry::make('nip')
                            ->label('NIP')
                            ->copyable(),
                        TextEntry::make('gender')
                            ->label('Jenis Kelamin')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                                default => $state,
                            }),
                        TextEntry::make('user.email')
                            ->label('Alamat Email')
                            ->copyable(),
                        TextEntry::make('user.phone')
                            ->label('Nomor Telepon')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->placeholder('Belum diisi')
                            ->columnSpanFull(),
                        TextEntry::make('user.status')
                            ->label('Status Akun')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger')
                            ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),
                    ]),
                Section::make('Informasi Sistem')
                    ->icon(Heroicon::OutlinedClock)
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
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

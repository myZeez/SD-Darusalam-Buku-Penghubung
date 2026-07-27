<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Siswa')
                    ->description('Data diri utama siswa yang terdaftar di sekolah.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        ImageEntry::make('photo')
                            ->label('Foto Siswa')
                            ->imageSize(112)
                            ->square(),
                        TextEntry::make('name')
                            ->label('Nama Siswa'),
                        TextEntry::make('nis')
                            ->label('NIS')
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
                        TextEntry::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date('d M Y')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('status')
                            ->label('Status Siswa')
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
                Section::make('Kelas dan Akun Siswa')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextEntry::make('class.name')
                            ->label('Kelas')
                            ->placeholder('Belum ditempatkan'),
                        TextEntry::make('class.teacher.user.name')
                            ->label('Wali Kelas')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('class.room')
                            ->label('Ruang Kelas')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('user.email')
                            ->label('Email Siswa')
                            ->copyable()
                            ->placeholder('Belum dibuat'),
                        TextEntry::make('user.phone')
                            ->label('Telepon Siswa')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('user.status')
                            ->label('Status Akun Siswa')
                            ->badge()
                            ->color(fn (?string $state): string => $state === 'active' ? 'success' : 'danger')
                            ->formatStateUsing(fn (?string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif')
                            ->placeholder('Belum dibuat'),
                    ]),
                Section::make('Orang Tua dan Keluarga')
                    ->description('Akun ini dapat terhubung dengan satu atau beberapa siswa dalam keluarga yang sama.')
                    ->icon(Heroicon::OutlinedHome)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextEntry::make('parent.user.name')
                            ->label('Nama Akun Orang Tua')
                            ->placeholder('Belum dihubungkan'),
                        TextEntry::make('parent.user.email')
                            ->label('Email Orang Tua')
                            ->copyable()
                            ->placeholder('Belum dihubungkan'),
                        TextEntry::make('parent.phone')
                            ->label('Telepon Orang Tua')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('parent.father_name')
                            ->label('Nama Ayah')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('parent.mother_name')
                            ->label('Nama Ibu')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('parent.address')
                            ->label('Alamat Keluarga')
                            ->placeholder('Belum diisi')
                            ->columnSpanFull(),
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

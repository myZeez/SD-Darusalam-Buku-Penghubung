<?php

namespace App\Filament\Resources\SchoolActivities\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SchoolActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Aktivitas')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextEntry::make('student.name')
                            ->label('Siswa'),
                        TextEntry::make('teacher.user.name')
                            ->label('Guru')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('activity_date')
                            ->label('Tanggal Aktivitas')
                            ->date('d M Y'),
                        TextEntry::make('attendance')
                            ->label('Kehadiran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'present' => 'success',
                                'sick' => 'warning',
                                'permission' => 'info',
                                'absent' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'present' => 'Hadir',
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                                'absent' => 'Alpa',
                                default => $state,
                            }),
                    ]),
                Section::make('Aktivitas Siswa')
                    ->description('Checklist dikelompokkan menurut empat kategori tetap pada Buku Penghubung.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('activity_groups')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record): array => $record->resolvedActivityGroups())
                            ->view('filament.infolists.entries.activity-groups'),
                    ]),
                Section::make('Catatan dan Dokumentasi')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->columns([
                        'lg' => 2,
                    ])
                    ->schema([
                        TextEntry::make('note')
                            ->label('Catatan Guru')
                            ->placeholder('Belum ada catatan'),
                        ImageEntry::make('photo')
                            ->label('Foto Aktivitas')
                            ->imageSize(160)
                            ->square(),
                    ]),
                Section::make('Informasi Sistem')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
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

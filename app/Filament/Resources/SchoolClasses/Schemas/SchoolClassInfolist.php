<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use App\Models\SchoolClass;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SchoolClassInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kelas')
                    ->description('Ringkasan wali kelas, tahun ajaran, dan jumlah siswa.')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Kelas'),
                        TextEntry::make('academicPeriod.label')
                            ->label('Periode Akademik')
                            ->placeholder(fn (SchoolClass $record): string => $record->academic_year),
                        TextEntry::make('grade_level')
                            ->label('Tingkat')
                            ->formatStateUsing(fn (?int $state): string => $state ? "Kelas {$state}" : 'Belum ditentukan'),
                        TextEntry::make('teacher.user.name')
                            ->label('Guru Utama')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('teacher.nip')
                            ->label('NIP Guru Utama')
                            ->placeholder('Belum tersedia'),
                        TextEntry::make('assistantTeacher.user.name')
                            ->label('Guru Pendamping')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('assistantTeacher.nip')
                            ->label('NIP Guru Pendamping')
                            ->placeholder('Belum tersedia'),
                        TextEntry::make('jumlah_siswa')
                            ->label('Jumlah Siswa')
                            ->state(fn (SchoolClass $record): int => $record->students()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('capacity')
                            ->label('Kapasitas Maksimum')
                            ->suffix(' siswa'),
                        TextEntry::make('room')
                            ->label('Ruang Kelas')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('description')
                            ->label('Deskripsi dan Catatan')
                            ->placeholder('Belum ada catatan')
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

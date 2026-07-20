<?php

namespace App\Filament\Resources\Extracurriculars\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ExtracurricularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Ekstrakurikuler')
                ->description('Tentukan nama kegiatan, guru pembina, status, dan gambaran singkat kegiatan.')
                ->icon(Heroicon::OutlinedTrophy)
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Ekstrakurikuler')
                        ->unique(ignoreRecord: true)
                        ->required(),
                    Select::make('teacher_id')
                        ->label('Guru Pembina')
                        ->relationship('teacher', 'nip')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->user?->name ?? $record->nip ?? 'Guru')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Aktif',
                            'inactive' => 'Tidak Aktif',
                        ])
                        ->default('active')
                        ->required(),
                    Textarea::make('description')
                        ->label('Deskripsi Kegiatan')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
            Section::make('Jadwal dan Pelaksanaan')
                ->description('Atur waktu latihan rutin, lokasi, dan batas peserta yang dapat ditampung.')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 4])
                ->schema([
                    Select::make('day_of_week')
                        ->label('Hari Latihan')
                        ->options([
                            'monday' => 'Senin',
                            'tuesday' => 'Selasa',
                            'wednesday' => 'Rabu',
                            'thursday' => 'Kamis',
                            'friday' => 'Jumat',
                            'saturday' => 'Sabtu',
                        ]),
                    TimePicker::make('start_time')
                        ->label('Jam Mulai')
                        ->seconds(false),
                    TimePicker::make('end_time')
                        ->label('Jam Selesai')
                        ->seconds(false)
                        ->after('start_time'),
                    TextInput::make('location')
                        ->label('Lokasi')
                        ->columnSpan(['xl' => 2]),
                    TextInput::make('capacity')
                        ->label('Kapasitas Peserta')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(200)
                        ->default(30)
                        ->required(),
                ]),
        ]);
    }
}

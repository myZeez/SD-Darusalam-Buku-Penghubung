<?php

namespace App\Filament\Resources\SchoolSettings\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SchoolSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Sekolah')
                ->description('Informasi utama yang digunakan pada laporan dan dokumen sekolah.')
                ->icon(Heroicon::OutlinedBuildingLibrary)
                ->columns(['md' => 2])
                ->schema([
                    FileUpload::make('logo')
                        ->label('Logo Sekolah')
                        ->image()
                        ->directory('school')
                        ->columnSpanFull(),
                    TextInput::make('school_name')
                        ->label('Nama Sekolah')
                        ->required()
                        ->default('Sekolah Dasar Islam Darussalam'),
                    TextInput::make('npsn')
                        ->label('NPSN'),
                    TextInput::make('principal_name')
                        ->label('Nama Kepala Sekolah'),
                    TextInput::make('academic_year')
                        ->label('Tahun Ajaran Aktif')
                        ->placeholder('2026/2027')
                        ->required(),
                    TextInput::make('phone')
                        ->label('Nomor Telepon')
                        ->tel(),
                    TextInput::make('email')
                        ->label('Alamat Email')
                        ->email(),
                    Textarea::make('address')
                        ->label('Alamat Sekolah')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            Section::make('Aturan Kehadiran')
                ->description('Jam ini menjadi dasar penentuan tepat waktu atau terlambat di loket.')
                ->icon(Heroicon::OutlinedClock)
                ->columns(['md' => 2])
                ->schema([
                    TimePicker::make('school_start_time')
                        ->label('Jam Masuk Sekolah')
                        ->seconds(false)
                        ->required(),
                    TextInput::make('late_tolerance_minutes')
                        ->label('Toleransi Keterlambatan')
                        ->suffix('menit')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(120)
                        ->required(),
                    CheckboxList::make('school_days')
                        ->label('Hari Sekolah Aktif')
                        ->options([
                            'monday' => 'Senin',
                            'tuesday' => 'Selasa',
                            'wednesday' => 'Rabu',
                            'thursday' => 'Kamis',
                            'friday' => 'Jumat',
                            'saturday' => 'Sabtu',
                        ])
                        ->columns(['sm' => 2, 'lg' => 3])
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('timezone')
                        ->label('Zona Waktu')
                        ->required()
                        ->default('Asia/Jakarta')
                        ->readOnly(),
                ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\AcademicPeriods\Schemas;

use App\Support\AcademicCalendar;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AcademicPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Periode Akademik')
                ->description('Tetapkan tahun ajaran dan semester yang menjadi acuan kelas, penugasan, dan jadwal pelajaran.')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(['md' => 2])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('academic_year')
                        ->label('Tahun Ajaran')
                        ->placeholder('2026/2027')
                        ->rules(['regex:/^\d{4}\/\d{4}$/'])
                        ->validationMessages([
                            'regex' => 'Gunakan format tahun ajaran seperti 2026/2027.',
                        ])
                        ->maxLength(9)
                        ->required(),
                    Select::make('semester')
                        ->label('Semester')
                        ->options(AcademicCalendar::SEMESTERS)
                        ->native(false)
                        ->required(),
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->afterOrEqual('start_date')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Jadikan Periode Aktif')
                        ->helperText('Mengaktifkan periode ini akan menonaktifkan periode sebelumnya.')
                        ->default(false)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

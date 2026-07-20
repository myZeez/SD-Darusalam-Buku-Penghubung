<?php

namespace App\Filament\Resources\AcademicPeriods\Schemas;

use App\Support\AcademicCalendar;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AcademicPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Periode')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('academic_year')->label('Tahun Ajaran'),
                    TextEntry::make('semester')
                        ->label('Semester')
                        ->formatStateUsing(fn (?string $state): string => AcademicCalendar::semesterLabel($state)),
                    IconEntry::make('is_active')->label('Periode Aktif')->boolean(),
                    TextEntry::make('start_date')->label('Tanggal Mulai')->date('d M Y'),
                    TextEntry::make('end_date')->label('Tanggal Selesai')->date('d M Y'),
                    TextEntry::make('classes_count')
                        ->label('Jumlah Kelas')
                        ->state(fn ($record): int => $record->classes()->count())
                        ->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('Tidak ada catatan')->columnSpanFull(),
                ]),
        ]);
    }
}

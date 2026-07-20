<?php

namespace App\Filament\Resources\LessonPeriods\Schemas;

use App\Support\AcademicCalendar;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LessonPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Periode')
                ->icon(Heroicon::OutlinedClock)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('academicPeriod.label')->label('Periode Akademik'),
                    TextEntry::make('name')->label('Nama Periode'),
                    TextEntry::make('sequence')->label('Urutan')->badge(),
                    TextEntry::make('start_time')->label('Jam Mulai')->time('H:i'),
                    TextEntry::make('end_time')->label('Jam Selesai')->time('H:i'),
                    TextEntry::make('type')
                        ->label('Jenis')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => AcademicCalendar::PERIOD_TYPES[$state] ?? (string) $state),
                    IconEntry::make('is_active')->label('Status Aktif')->boolean(),
                ]),
        ]);
    }
}

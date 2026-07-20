<?php

namespace App\Filament\Resources\LessonSchedules\Schemas;

use App\Support\AcademicCalendar;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LessonScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Jadwal Pelajaran')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(['md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('day_of_week')
                        ->label('Hari')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => AcademicCalendar::dayLabel($state)),
                    TextEntry::make('lessonPeriod.name')->label('Periode'),
                    TextEntry::make('rentang_waktu')
                        ->label('Waktu')
                        ->state(fn ($record): string => substr($record->lessonPeriod?->start_time ?? '', 0, 5)
                            .' - '.substr($record->lessonPeriod?->end_time ?? '', 0, 5)),
                    TextEntry::make('teachingAssignment.subject.name')->label('Mata Pelajaran'),
                    TextEntry::make('teachingAssignment.schoolClass.name')->label('Kelas'),
                    TextEntry::make('teachingAssignment.teacher.user.name')->label('Guru Pengajar'),
                    TextEntry::make('teachingAssignment.academicPeriod.label')->label('Periode Akademik'),
                    TextEntry::make('room')->label('Ruangan')->placeholder('Mengikuti ruang kelas'),
                    IconEntry::make('is_active')->label('Jadwal Aktif')->boolean(),
                    TextEntry::make('notes')->label('Keterangan')->placeholder('Tidak ada keterangan')->columnSpanFull(),
                ]),
        ]);
    }
}

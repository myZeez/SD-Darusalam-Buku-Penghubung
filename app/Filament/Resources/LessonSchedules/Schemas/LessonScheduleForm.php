<?php

namespace App\Filament\Resources\LessonSchedules\Schemas;

use App\Models\LessonPeriod;
use App\Models\TeachingAssignment;
use App\Support\AcademicCalendar;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class LessonScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Penempatan Jadwal')
                ->description('Pilih penugasan Guru terlebih dahulu. Kelas dan mata pelajaran mengikuti penugasan tersebut.')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(['md' => 2])
                ->columnSpanFull()
                ->schema([
                    Select::make('teaching_assignment_id')
                        ->relationship(
                            name: 'teachingAssignment',
                            titleAttribute: 'id',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->where('is_active', true)
                                ->with(['academicPeriod', 'teacher.user', 'schoolClass', 'subject'])
                                ->latest('academic_period_id'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (TeachingAssignment $record): string => sprintf(
                            '%s - %s - %s (%s)',
                            $record->schoolClass?->name ?? 'Kelas',
                            $record->subject?->name ?? 'Mata pelajaran',
                            $record->teacher?->user?->name ?? 'Guru',
                            $record->academicPeriod?->label ?? 'Periode',
                        ))
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $assignment = TeachingAssignment::query()->with('schoolClass')->find($state);
                            $set('lesson_period_id', null);
                            $set('room', $assignment?->schoolClass?->room);
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->label('Penugasan Mengajar')
                        ->columnSpanFull(),
                    Select::make('day_of_week')
                        ->label('Hari')
                        ->options(AcademicCalendar::DAYS)
                        ->native(false)
                        ->required(),
                    Select::make('lesson_period_id')
                        ->relationship(
                            name: 'lessonPeriod',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, Get $get): Builder {
                                $academicPeriodId = TeachingAssignment::query()
                                    ->whereKey($get('teaching_assignment_id'))
                                    ->value('academic_period_id');

                                return $query
                                    ->where('type', 'lesson')
                                    ->where('is_active', true)
                                    ->when($academicPeriodId, fn (Builder $query): Builder => $query->where('academic_period_id', $academicPeriodId))
                                    ->orderBy('sequence');
                            },
                        )
                        ->getOptionLabelFromRecordUsing(fn (LessonPeriod $record): string => sprintf(
                            '%s | %s-%s',
                            $record->name,
                            substr($record->start_time, 0, 5),
                            substr($record->end_time, 0, 5),
                        ))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Periode Pelajaran'),
                    TextInput::make('room')
                        ->label('Ruangan')
                        ->placeholder('Mengikuti ruang kelas jika tersedia')
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label('Jadwal Aktif')
                        ->default(true),
                    Textarea::make('notes')
                        ->label('Keterangan atau Perubahan')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

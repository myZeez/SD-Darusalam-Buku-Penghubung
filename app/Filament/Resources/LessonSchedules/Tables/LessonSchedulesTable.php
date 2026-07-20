<?php

namespace App\Filament\Resources\LessonSchedules\Tables;

use App\Filament\Resources\LessonSchedules\LessonScheduleResource;
use App\Models\AcademicPeriod;
use App\Models\LessonPeriod;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Support\AcademicCalendar;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonSchedulesTable
{
    public static function configure(Table $table): Table
    {
        $isTeacher = auth()->user()?->hasRole('guru') ?? false;

        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'lessonPeriod',
                    'teachingAssignment.academicPeriod',
                    'teachingAssignment.teacher.user',
                    'teachingAssignment.schoolClass',
                    'teachingAssignment.subject',
                ])
                ->orderByRaw("CASE day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 WHEN 'saturday' THEN 6 ELSE 7 END")
                ->orderBy(LessonPeriod::query()
                    ->select('sequence')
                    ->whereColumn('lesson_periods.id', 'lesson_schedules.lesson_period_id')))
            ->columns($isTeacher
                ? [
                    ViewColumn::make('ringkasan_jadwal_mengajar')
                        ->label('Jadwal Mengajar')
                        ->view('filament.tables.columns.teacher-lesson-schedule-card'),
                ]
                : [
                    TextColumn::make('day_of_week')
                        ->label('Hari')
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn (?string $state): string => AcademicCalendar::dayLabel($state)),
                    TextColumn::make('lessonPeriod.name')
                        ->label('Waktu')
                        ->description(fn (LessonSchedule $record): string => sprintf(
                            '%s-%s',
                            substr($record->lessonPeriod?->start_time ?? '', 0, 5),
                            substr($record->lessonPeriod?->end_time ?? '', 0, 5),
                        ))
                        ->sortable(),
                    TextColumn::make('teachingAssignment.subject.name')
                        ->label('Mata Pelajaran')
                        ->description(fn (LessonSchedule $record): ?string => $record->teachingAssignment?->subject?->code)
                        ->searchable()
                        ->weight('bold'),
                    TextColumn::make('teachingAssignment.schoolClass.name')->label('Kelas')->badge()->searchable(),
                    TextColumn::make('teachingAssignment.teacher.user.name')->label('Guru')->searchable()->wrap(),
                    TextColumn::make('room')->label('Ruang')->placeholder('-')->toggleable(),
                    IconColumn::make('is_active')->label('Aktif')->boolean(),
                ])
            ->filters($isTeacher
                ? []
                : [
                    SelectFilter::make('day_of_week')->label('Hari')->options(AcademicCalendar::DAYS),
                    SelectFilter::make('academic_period_id')
                        ->label('Periode Akademik')
                        ->options(fn (): array => AcademicPeriod::query()->orderByDesc('start_date')->get()->mapWithKeys(
                            fn (AcademicPeriod $period): array => [$period->id => $period->label],
                        )->all())
                        ->query(fn (Builder $query, array $data): Builder => $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $periodId): Builder => $query->whereHas(
                                'teachingAssignment',
                                fn (Builder $query): Builder => $query->where('academic_period_id', $periodId),
                            ),
                        )),
                    SelectFilter::make('class_id')
                        ->label('Kelas')
                        ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->query(fn (Builder $query, array $data): Builder => $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $classId): Builder => $query->whereHas(
                                'teachingAssignment',
                                fn (Builder $query): Builder => $query->where('class_id', $classId),
                            ),
                        )),
                    TernaryFilter::make('is_active')->label('Status Aktif'),
                ])
            ->recordUrl(null)
            ->recordActions($isTeacher
                ? []
                : [
                    ViewAction::make(),
                    EditAction::make()->visible(fn (LessonSchedule $record): bool => LessonScheduleResource::canEdit($record)),
                ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => LessonScheduleResource::canDeleteAny()),
                ]),
            ])
            ->when($isTeacher, fn (Table $table): Table => $table
                ->defaultPaginationPageOption(20)
                ->paginationPageOptions([20, 40, 60]))
            ->emptyStateHeading('Belum ada jadwal pelajaran')
            ->emptyStateDescription('Tambahkan penugasan dan periode jam sebelum menyusun jadwal mingguan.')
            ->emptyStateIcon('gmdi-calendar-view-week-o');
    }
}

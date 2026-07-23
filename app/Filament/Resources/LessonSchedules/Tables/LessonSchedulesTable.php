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
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
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
            ->columns([
                ViewColumn::make('ringkasan_jadwal')
                    ->label('Jadwal mingguan')
                    ->view('filament.tables.columns.teacher-lesson-schedule-card')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'teachingAssignment.subject',
                        fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"),
                    )),
            ])
            ->groups([
                Group::make('day_of_week')
                    ->label('Hari')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (LessonSchedule $record): string => AcademicCalendar::dayLabel($record->day_of_week)),
            ])
            ->defaultGroup('day_of_week')
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

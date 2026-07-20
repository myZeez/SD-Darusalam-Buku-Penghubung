<?php

namespace App\Filament\Widgets;

use App\Models\LessonPeriod;
use App\Models\LessonSchedule;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CurrentLessonSchedule extends Widget
{
    use CanPoll;

    protected static ?int $sort = -4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.current-lesson-schedule';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['guru', 'siswa', 'orang_tua']) ?? false;
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }

    /**
     * @return array{currentSchedules: Collection<int, LessonSchedule>, currentTime: string}
     */
    protected function getViewData(): array
    {
        return [
            'currentSchedules' => $this->getCurrentSchedules(),
            'currentTime' => now()->format('H:i'),
        ];
    }

    /**
     * @return Collection<int, LessonSchedule>
     */
    protected function getCurrentSchedules(): Collection
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['guru', 'siswa', 'orang_tua'])) {
            return new Collection;
        }

        $now = now();
        $accessibleClassIds = ($user->hasRole('guru') ? $user->managedClasses() : $user->accessibleClasses())
            ->select('classes.id');

        return LessonSchedule::query()
            ->where('is_active', true)
            ->where('day_of_week', strtolower($now->englishDayOfWeek))
            ->whereHas('teachingAssignment', fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->whereIn('class_id', $accessibleClassIds))
            ->whereHas('lessonPeriod', fn (Builder $query): Builder => $query
                ->where('is_active', true)
                ->where('type', 'lesson')
                ->where('start_time', '<=', $now->format('H:i:s'))
                ->where('end_time', '>', $now->format('H:i:s')))
            ->with([
                'lessonPeriod',
                'teachingAssignment.schoolClass',
                'teachingAssignment.subject',
                'teachingAssignment.teacher.user',
            ])
            ->orderBy(LessonPeriod::query()
                ->select('sequence')
                ->whereColumn('lesson_periods.id', 'lesson_schedules.lesson_period_id'))
            ->get();
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DailySchoolActivities;
use App\Models\Student;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PendingSchoolReports extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.pending-school-reports';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('guru') ?? false;
    }

    /**
     * @return array{pendingStudents: Collection<int, Student>, reportedCount: int, totalCount: int, reportIndexUrl: string}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if (! $user?->hasRole('guru')) {
            return [
                'pendingStudents' => new Collection,
                'reportedCount' => 0,
                'totalCount' => 0,
                'reportIndexUrl' => DailySchoolActivities::getUrl(),
            ];
        }

        $students = $user->managedStudents()
            ->where('status', 'active');
        $totalCount = (clone $students)->count();
        $pendingStudents = $students
            ->whereDoesntHave('schoolActivities', fn (Builder $query): Builder => $query
                ->whereDate('activity_date', today()))
            ->with('class')
            ->orderBy('name')
            ->get();

        return [
            'pendingStudents' => $pendingStudents,
            'reportedCount' => $totalCount - $pendingStudents->count(),
            'totalCount' => $totalCount,
            'reportIndexUrl' => DailySchoolActivities::getUrl(),
        ];
    }
}

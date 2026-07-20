<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ActivityReports;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use App\Services\DevelopmentAspectService;
use Filament\Widgets\Widget;

class ParentDashboardOverview extends Widget
{
    protected static ?int $sort = -5;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.parent-dashboard-overview';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('orang_tua') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $profile = $user?->parentProfile;
        $students = $user?->accessibleStudents()
            ->with(['class.teacher.user'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get() ?? collect();
        $studentIds = $students->modelKeys();
        $from = now()->subDays(6)->toDateString();

        $schoolActivities = SchoolActivity::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('activity_date', '>=', $from)
            ->whereDate('activity_date', '<=', today())
            ->get();
        $homeActivities = HomeActivity::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('activity_date', '>=', $from)
            ->whereDate('activity_date', '<=', today())
            ->get();

        return [
            'children' => $students,
            'profileName' => $user?->name ?? 'Orang Tua/Wali',
            'profilePhone' => $profile?->phone ?: $user?->phone,
            'profileUrl' => ParentProfileResource::getNavigationUrl(),
            'reportUrl' => ActivityReports::getUrl(),
            'schoolActivityCount' => $schoolActivities->count(),
            'homeActivityCount' => $homeActivities->count(),
            'developmentAspects' => app(DevelopmentAspectService::class)->summarize($schoolActivities, $homeActivities),
        ];
    }
}

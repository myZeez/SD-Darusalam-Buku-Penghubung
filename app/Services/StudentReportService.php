<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use App\Models\User;
use App\Support\HomeActivityTemplate;
use App\Support\SchoolActivityTemplate;

class StudentReportService
{
    public function __construct(
        private readonly DevelopmentAspectService $developmentAspects,
        private readonly ActivityScoreService $activityScores,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $user, string $from, string $to, ?int $studentId = null): array
    {
        $students = $user->accessibleStudents()
            ->with(['class.teacher.user', 'parent.user'])
            ->when($studentId, fn ($query) => $query->whereKey($studentId))
            ->orderBy('name')
            ->get();

        $studentIds = $students->modelKeys();

        $attendanceRecords = AttendanceRecord::query()
            ->with('arrival')
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', '>=', $from)
            ->whereDate('attendance_date', '<=', $to)
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('student_id');

        $schoolActivities = SchoolActivity::query()
            ->with('teacher.user')
            ->whereIn('student_id', $studentIds)
            ->whereDate('activity_date', '>=', $from)
            ->whereDate('activity_date', '<=', $to)
            ->orderBy('activity_date')
            ->get()
            ->groupBy('student_id');

        $homeActivities = HomeActivity::query()
            ->with('parent.user')
            ->whereIn('student_id', $studentIds)
            ->whereDate('activity_date', '>=', $from)
            ->whereDate('activity_date', '<=', $to)
            ->orderBy('activity_date')
            ->get()
            ->groupBy('student_id');

        $reports = $students->map(function ($student) use ($attendanceRecords, $schoolActivities, $homeActivities, $from, $to): array {
            $attendance = $attendanceRecords->get($student->id, collect())->values();
            $school = $schoolActivities->get($student->id, collect())->values();
            $home = $homeActivities->get($student->id, collect())->values();

            return [
                'student' => $student,
                'attendance_records' => $attendance,
                'school_activities' => $school,
                'home_activities' => $home,
                'summary' => [
                    'attendance' => $attendance->count(),
                    'present' => $attendance->where('status', 'present')->count(),
                    'on_time' => $attendance->filter(fn ($record): bool => $record->arrival?->status === 'on_time')->count(),
                    'late' => $attendance->where('is_late', true)->count(),
                    'school_activities' => $school->count(),
                    'home_activities' => $home->count(),
                    'school_activity_scores' => $this->activityScores->summarize(
                        $school,
                        SchoolActivityTemplate::forGrade($student->class?->grade_level),
                        $from,
                        $to,
                        'school',
                    ),
                    'home_activity_scores' => $this->activityScores->summarize(
                        $home,
                        HomeActivityTemplate::forGrade($student->class?->grade_level),
                        $from,
                        $to,
                        'home',
                    ),
                    'development_aspects' => $this->developmentAspects->summarize($school, $home),
                ],
            ];
        })->values();

        return [
            'from' => $from,
            'to' => $to,
            'reports' => $reports,
            'summary' => [
                'students' => $students->count(),
                'attendance' => $reports->sum(fn (array $report): int => $report['summary']['attendance']),
                'on_time' => $reports->sum(fn (array $report): int => $report['summary']['on_time']),
                'late' => $reports->sum(fn (array $report): int => $report['summary']['late']),
                'school_activities' => $reports->sum(fn (array $report): int => $report['summary']['school_activities']),
                'home_activities' => $reports->sum(fn (array $report): int => $report['summary']['home_activities']),
            ],
        ];
    }
}

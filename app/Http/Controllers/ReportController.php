<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\StudentReportService;
use App\Support\FixedActivityChecklist;
use App\Support\HomeActivityTemplate;
use App\Support\SchoolActivityTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function activitySummary(Request $request)
    {
        $user = $request->user();

        abort_unless(
            ($user?->can('export reports') ?? false)
            && (($user?->isAdmin() ?? false) || $user?->hasRole('guru')),
            403,
        );

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'student_id' => ['nullable', 'exists:students,id'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        if ($studentId = $validated['student_id'] ?? null) {
            abort_unless($user->canAccessStudent((int) $studentId), 403);
        }

        $report = app(StudentReportService::class)->build(
            $user,
            $from,
            $to,
            filled($validated['student_id'] ?? null) ? (int) $validated['student_id'] : null,
        );

        $studentName = $report['reports']->count() === 1
            ? Str::slug($report['reports']->first()['student']->name)
            : 'semua-siswa';

        return Pdf::loadView('reports.activity-summary', [
            'report' => $report,
            'settings' => SchoolSetting::current(),
        ])
            ->setPaper('a4')
            ->download("laporan-siswa-{$studentName}-{$from}-{$to}.pdf");
    }

    public function classAttendance(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['required', 'date'],
        ]);

        $classId = (int) $validated['class_id'];
        $canManageOwnClass = ($user?->hasRole('guru') ?? false)
            && ($user?->can('manage attendances') ?? false)
            && $user->managedClasses()->whereKey($classId)->exists();
        $canExportAllClasses = ($user?->isAdmin() ?? false)
            && ($user?->can('export reports') ?? false);

        abort_unless($canManageOwnClass || $canExportAllClasses, 403);

        $schoolClass = SchoolClass::query()
            ->with([
                'teacher.user',
                'students' => fn ($query) => $query->where('status', 'active')->orderBy('name'),
            ])
            ->findOrFail($classId);
        $records = AttendanceRecord::query()
            ->with(['arrival', 'parentSubmission'])
            ->where('class_id', $classId)
            ->whereDate('attendance_date', $validated['date'])
            ->get()
            ->keyBy('student_id');
        $summary = collect(['present', 'sick', 'permission', 'absent'])
            ->mapWithKeys(fn (string $status): array => [
                $status => $records->where('status', $status)->count(),
            ]);

        return Pdf::loadView('reports.class-attendance', [
            'date' => $validated['date'],
            'records' => $records,
            'schoolClass' => $schoolClass,
            'settings' => SchoolSetting::current(),
            'summary' => $summary,
        ])
            ->setPaper('a4')
            ->download('presensi-'.Str::slug($schoolClass->name).'-'.$validated['date'].'.pdf');
    }

    public function dailySchoolActivities(Request $request)
    {
        $user = $request->user();

        abort_unless(
            ($user?->can('manage school activities') ?? false)
            && ($user?->can('export reports') ?? false),
            403,
        );

        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['required', 'date'],
        ]);

        $schoolClass = $user->managedClasses()
            ->with('teacher.user')
            ->whereKey((int) $validated['class_id'])
            ->firstOrFail();
        $date = CarbonImmutable::parse($validated['date'])->toDateString();
        $students = Student::query()
            ->where('class_id', $schoolClass->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $template = SchoolActivityTemplate::forGrade($schoolClass->grade_level);
        $records = SchoolActivity::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('activity_date', $date)
            ->get()
            ->keyBy('student_id');
        $attendance = AttendanceRecord::query()
            ->where('class_id', $schoolClass->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');
        $attendanceLabels = [
            'present' => 'Hadir',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'absent' => 'Alpa',
        ];
        $rows = $students->map(function (Student $student) use ($records, $attendance, $attendanceLabels, $template): array {
            $record = $records->get($student->id);
            $attendanceRecord = $attendance->get($student->id);

            return [
                'student' => $student,
                'checks' => FixedActivityChecklist::checksFrom($template, $record?->resolvedActivityGroups() ?? []),
                'attendance' => $attendanceRecord
                    ? ($attendanceLabels[$attendanceRecord->status] ?? $attendanceRecord->status)
                    : 'Belum dicatat',
                'is_late' => (bool) $attendanceRecord?->is_late,
                'note' => $record?->note,
            ];
        });

        return Pdf::loadView('reports.daily-school-activities', [
            'date' => $date,
            'rows' => $rows,
            'schoolClass' => $schoolClass,
            'settings' => SchoolSetting::current(),
            'template' => $template,
        ])
            ->setPaper('a4')
            ->download('laporan-sekolah-'.Str::slug($schoolClass->name).'-'.$date.'.pdf');
    }

    public function dailyHomeActivities(Request $request)
    {
        $user = $request->user();

        abort_unless(
            ($user?->can('view home activities') ?? false)
            && ($user?->can('export reports') ?? false)
            && (($user?->isAdmin() ?? false) || $user?->hasRole('guru')),
            403,
        );

        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $date = CarbonImmutable::parse($validated['date'])->toDateString();
        $isParent = $user->hasRole('orang_tua');

        if ($isParent) {
            abort_unless(filled($validated['student_id'] ?? null), 422);

            $student = $user->accessibleStudents()
                ->with('class')
                ->whereKey((int) $validated['student_id'])
                ->where('status', 'active')
                ->firstOrFail();
            $schoolClass = $student->class;
            $students = collect([$student]);
        } else {
            abort_unless(filled($validated['class_id'] ?? null), 422);

            $schoolClass = $user->managedClasses()
                ->whereKey((int) $validated['class_id'])
                ->firstOrFail();
            $students = Student::query()
                ->where('class_id', $schoolClass->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $student = null;
        }

        $template = HomeActivityTemplate::forGrade($schoolClass?->grade_level);
        $records = HomeActivity::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('activity_date', $date)
            ->get()
            ->keyBy('student_id');
        $rows = $students->map(function (Student $student) use ($records, $template): array {
            $record = $records->get($student->id);

            return [
                'student' => $student,
                'checks' => FixedActivityChecklist::checksFrom($template, $record?->resolvedActivityGroups() ?? []),
                'note' => $record?->note,
                'submitted_at' => $record?->submitted_at,
            ];
        });

        $filename = $isParent
            ? 'aktivitas-rumah-'.Str::slug($student->name).'-'.$date.'.pdf'
            : 'aktivitas-rumah-'.Str::slug($schoolClass->name).'-'.$date.'.pdf';

        return Pdf::loadView('reports.daily-home-activities', [
            'date' => $date,
            'isParent' => $isParent,
            'rows' => $rows,
            'schoolClass' => $schoolClass,
            'settings' => SchoolSetting::current(),
            'student' => $student,
            'template' => $template,
        ])
            ->setPaper('a4')
            ->download($filename);
    }
}

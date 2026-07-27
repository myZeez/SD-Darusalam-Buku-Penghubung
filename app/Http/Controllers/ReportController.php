<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Services\StudentReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function activitySummary(Request $request)
    {
        $user = $request->user();

        abort_unless(
            ($user?->can('export reports') ?? false)
            && (($user?->isAdmin() ?? false) || $user?->hasAnyRole(['guru', 'orang_tua'])),
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
            ->setPaper('a4', 'landscape')
            ->download('presensi-'.Str::slug($schoolClass->name).'-'.$validated['date'].'.pdf');
    }
}

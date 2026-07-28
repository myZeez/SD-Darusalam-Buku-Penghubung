<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecord;
use App\Models\SchoolActivity;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\ActivityScoreService;
use App\Support\FixedActivityChecklist;
use App\Support\SchoolActivityTemplate;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class DailySchoolActivities extends Page
{
    protected static ?string $title = 'Laporan Sekolah';

    protected static ?string $navigationLabel = 'Laporan Sekolah';

    protected static string|\UnitEnum|null $navigationGroup = 'Buku Penghubung';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-assignment-o';

    protected static ?string $slug = 'laporan-sekolah-harian';

    protected string $view = 'filament.pages.daily-school-activities';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $selectedDate = '';

    public ?int $selectedClassId = null;

    public ?int $selectedStudentId = null;

    public string $mode = 'activity';

    /** @var array<int, array<string, mixed>> */
    public array $classes = [];

    /** @var array<int, array<string, mixed>> */
    public array $students = [];

    /** @var array<int, array<string, bool>> */
    public array $checks = [];

    /** @var array<int, string> */
    public array $notes = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage school activities') ?? false)
            && $user->managedClasses()->exists();
    }

    public function getSubheading(): ?string
    {
        return 'Centang aktivitas tetap untuk seluruh kelas atau per siswa. Daftar mengikuti Buku Penghubung SIMPATI.';
    }

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
        $this->classes = auth()->user()
            ->managedClasses()
            ->withCount(['students' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolClass $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'grade_level' => $class->grade_level,
                'students_count' => $class->students_count,
            ])
            ->all();

        $this->selectedClassId = $this->classes[0]['id'] ?? null;
        $this->loadChecklist();

        $requestedStudentId = request()->integer('student_id');
        $requestedStudent = $requestedStudentId > 0
            ? auth()->user()->managedStudents()->whereKey($requestedStudentId)->first()
            : null;

        if ($requestedStudent?->class_id) {
            $this->selectedClassId = (int) $requestedStudent->class_id;
            $this->mode = 'student';
            $this->loadChecklist();
            $this->selectedStudentId = $requestedStudent->id;
        }
    }

    public function updatedSelectedDate(): void
    {
        $this->validateOnly('selectedDate', ['selectedDate' => ['required', 'date']]);
        $this->loadChecklist();
    }

    public function updatedSelectedClassId(): void
    {
        $this->selectedClassId = $this->selectedClassId ? (int) $this->selectedClassId : null;
        $this->loadChecklist();
    }

    public function updatedSelectedStudentId(): void
    {
        $this->selectedStudentId = $this->selectedStudentId ? (int) $this->selectedStudentId : null;
    }

    public function setMode(string $mode): void
    {
        abort_unless(in_array($mode, ['activity', 'student'], true), 422);
        $this->mode = $mode;
    }

    public function markActivityForAll(string $activityKey, bool $checked): void
    {
        abort_unless(collect(FixedActivityChecklist::items($this->template()))->contains('key', $activityKey), 422);

        if ($activityKey === 'on-time-arrival') {
            return;
        }

        foreach ($this->students as $student) {
            $this->checks[$student['id']][$activityKey] = $checked;
        }
    }

    public function markStudentActivities(bool $checked): void
    {
        $studentId = (int) $this->selectedStudentId;
        abort_unless(collect($this->students)->contains('id', $studentId), 422);

        foreach (FixedActivityChecklist::items($this->template()) as $item) {
            if ($item['key'] !== 'on-time-arrival') {
                $this->checks[$studentId][$item['key']] = $checked;
            }
        }
    }

    public function saveChecklist(): void
    {
        if (! $this->isSchoolDay()) {
            Notification::make()
                ->title('Tanggal ini bukan hari sekolah aktif')
                ->body('Pilih hari yang sudah diaktifkan Admin pada Pengaturan Sekolah.')
                ->warning()
                ->send();

            return;
        }

        $class = $this->authorizedClass();
        $date = CarbonImmutable::parse($this->selectedDate)->toDateString();
        $studentIds = collect($this->students)->pluck('id')->map(fn ($id): int => (int) $id);

        $this->validate([
            'selectedDate' => ['required', 'date'],
            'checks' => ['array'],
            'checks.*' => ['array'],
            'checks.*.*' => ['boolean'],
            'notes' => ['array'],
            'notes.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $attendance = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');
        $existing = SchoolActivity::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('activity_date', $date)
            ->get()
            ->keyBy('student_id');
        $teacherId = auth()->user()->teacher?->id ?? $class->teacher_id;
        $template = SchoolActivityTemplate::forGrade($class->grade_level);

        DB::transaction(function () use ($studentIds, $date, $attendance, $existing, $teacherId, $template): void {
            foreach ($studentIds as $studentId) {
                $attendanceRecord = $attendance->get($studentId);
                $studentChecks = $this->checks[$studentId] ?? [];
                $studentChecks['on-time-arrival'] = $attendanceRecord?->status === 'present'
                    && ! $attendanceRecord->is_late;

                $data = [
                    'teacher_id' => $teacherId,
                    'attendance' => $attendanceRecord?->status
                        ?? $existing->get($studentId)?->attendance
                        ?? 'present',
                    'activity_groups' => FixedActivityChecklist::apply($template, $studentChecks),
                    'note' => filled($this->notes[$studentId] ?? null)
                        ? trim($this->notes[$studentId])
                        : null,
                ];

                $record = $existing->get($studentId);

                if ($record) {
                    $record->update($data);
                } else {
                    SchoolActivity::query()->create([
                        'student_id' => $studentId,
                        'activity_date' => $date,
                        ...$data,
                    ]);
                }
            }
        });

        $this->loadChecklist();

        Notification::make()
            ->title('Checklist aktivitas sekolah tersimpan')
            ->body(count($this->students).' siswa diperbarui untuk '.$this->formattedDate().'.')
            ->success()
            ->send();
    }

    /** @return array<int, array<string, mixed>> */
    public function template(): array
    {
        $gradeLevel = collect($this->classes)
            ->firstWhere('id', (int) $this->selectedClassId)['grade_level'] ?? null;

        return SchoolActivityTemplate::forGrade($gradeLevel ? (int) $gradeLevel : null);
    }

    /** @return array{checked: int, total: int} */
    public function activityCompletion(string $activityKey): array
    {
        $checked = collect($this->students)
            ->filter(fn (array $student): bool => (bool) ($this->checks[$student['id']][$activityKey] ?? false))
            ->count();

        return ['checked' => $checked, 'total' => count($this->students)];
    }

    /** @return array{checked: int, total: int} */
    public function studentCompletion(int $studentId): array
    {
        $items = FixedActivityChecklist::items($this->template());
        $checked = collect($items)
            ->filter(fn (array $item): bool => (bool) ($this->checks[$studentId][$item['key']] ?? false))
            ->count();

        return ['checked' => $checked, 'total' => count($items)];
    }

    /** @return array<string, mixed>|null */
    public function selectedStudent(): ?array
    {
        return collect($this->students)->firstWhere('id', (int) $this->selectedStudentId);
    }

    /** @return array<int, array<string, mixed>> */
    public function weeklyScores(): array
    {
        if (! $this->selectedClassId) {
            return [];
        }

        $class = $this->authorizedClass();
        $selectedDate = CarbonImmutable::parse($this->selectedDate);
        $from = $selectedDate->startOfWeek()->toDateString();
        $to = $selectedDate->endOfWeek()->toDateString();
        $records = SchoolActivity::query()
            ->whereHas('student', fn ($query) => $query
                ->where('class_id', $class->id)
                ->where('status', 'active'))
            ->whereBetween('activity_date', [$from, $to])
            ->get();

        return app(ActivityScoreService::class)->summarize(
            $records,
            SchoolActivityTemplate::forGrade($class->grade_level),
            $from,
            $to,
            'school',
            count($this->students),
        );
    }

    public function formattedDate(): string
    {
        return CarbonImmutable::parse($this->selectedDate)->translatedFormat('l, d F Y');
    }

    public function isSchoolDay(): bool
    {
        $dayName = strtolower(CarbonImmutable::parse($this->selectedDate)->format('l'));

        return in_array($dayName, SchoolSetting::current()->school_days ?? [], true);
    }

    private function loadChecklist(): void
    {
        $this->students = [];
        $this->checks = [];
        $this->notes = [];
        $this->selectedStudentId = null;

        if (! $this->selectedClassId) {
            return;
        }

        $class = $this->authorizedClass();
        $date = CarbonImmutable::parse($this->selectedDate)->toDateString();
        $template = SchoolActivityTemplate::forGrade($class->grade_level);
        $records = SchoolActivity::query()
            ->whereHas('student', fn ($query) => $query->where('class_id', $class->id))
            ->whereDate('activity_date', $date)
            ->get()
            ->keyBy('student_id');
        $attendance = AttendanceRecord::query()
            ->where('class_id', $class->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        foreach (Student::query()
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get() as $student) {
            $record = $records->get($student->id);
            $attendanceRecord = $attendance->get($student->id);
            $checks = FixedActivityChecklist::checksFrom($template, $record?->resolvedActivityGroups() ?? []);
            $checks['on-time-arrival'] = $attendanceRecord?->status === 'present'
                && ! $attendanceRecord->is_late;

            $this->students[] = [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'initials' => str($student->name)
                    ->explode(' ')
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part): string => str($part)->substr(0, 1)->upper()->toString())
                    ->join(''),
                'attendance' => $attendanceRecord?->status,
                'attendance_label' => match ($attendanceRecord?->status) {
                    'present' => $attendanceRecord->is_late ? 'Hadir terlambat' : 'Hadir tepat waktu',
                    'sick' => 'Sakit',
                    'permission' => 'Izin',
                    'absent' => 'Alpa',
                    default => 'Presensi belum diisi',
                },
            ];
            $this->checks[$student->id] = $checks;
            $this->notes[$student->id] = $record?->note ?? '';
        }

        $this->selectedStudentId = $this->students[0]['id'] ?? null;
    }

    private function authorizedClass(): SchoolClass
    {
        abort_unless($this->selectedClassId, 404);

        return auth()->user()
            ->managedClasses()
            ->whereKey((int) $this->selectedClassId)
            ->firstOrFail();
    }
}

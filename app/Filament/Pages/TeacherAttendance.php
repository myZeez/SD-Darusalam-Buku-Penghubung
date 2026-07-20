<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Models\AttendanceRecord;
use App\Models\ParentSubmission;
use App\Models\Student;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherAttendance extends Page
{
    protected static ?string $title = 'Presensi Kelas';

    protected static ?string $navigationLabel = 'Presensi Kelas';

    protected static string|\UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-fact-check-o';

    protected static ?string $slug = 'presensi-kelas';

    protected string $view = 'filament.pages.teacher-attendance';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $selectedDate = '';

    public ?int $selectedClassId = null;

    /** @var array<int, array<string, mixed>> */
    public array $classes = [];

    /** @var array<int, array<string, mixed>> */
    public array $students = [];

    /** @var array<int, string|null> */
    public array $attendance = [];

    /** @var array<int, string> */
    public array $notes = [];

    /** @var array<int, array<string, mixed>> */
    public array $pendingSubmissions = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->hasRole('guru') ?? false)
            && ($user?->can('manage attendances') ?? false)
            && $user->managedClasses()->exists();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user?->hasRole('guru')) {
            return null;
        }

        $classIds = $user->managedClasses()->pluck('classes.id');
        $unrecorded = Student::query()
            ->where('status', 'active')
            ->whereIn('class_id', $classIds)
            ->whereDoesntHave(
                'attendanceRecords',
                fn ($query) => $query->whereDate('attendance_date', today()),
            )
            ->count();

        return $unrecorded > 0 ? (string) $unrecorded : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Siswa yang belum memiliki status presensi hari ini';
    }

    public function getSubheading(): ?string
    {
        return 'Periksa data dari Loket dan laporan orang tua, lalu lengkapi status seluruh siswa dalam satu kelas.';
    }

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
        $this->classes = auth()->user()
            ->managedClasses()
            ->withCount(['students' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get()
            ->map(fn ($class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'academic_year' => $class->academic_year,
                'students_count' => $class->students_count,
            ])
            ->all();

        $this->selectedClassId = $this->classes[0]['id'] ?? null;
        $this->loadAttendance();
    }

    public function updatedSelectedDate(): void
    {
        $this->validateOnly('selectedDate', [
            'selectedDate' => ['required', 'date'],
        ]);

        $this->loadAttendance();
    }

    public function updatedSelectedClassId(): void
    {
        $this->selectedClassId = $this->selectedClassId ? (int) $this->selectedClassId : null;
        $this->loadAttendance();
    }

    public function reloadAttendance(): void
    {
        $this->loadAttendance();

        Notification::make()
            ->title('Data presensi diperbarui')
            ->body('Check-in Loket dan laporan orang tua terbaru sudah dimuat.')
            ->success()
            ->send();
    }

    public function markAllPresent(): void
    {
        foreach ($this->students as $student) {
            $studentId = $student['id'];
            $this->attendance[$studentId] ??= 'present';
        }
    }

    public function saveAttendance(): void
    {
        $classId = $this->authorizedClassId();
        $studentIds = collect($this->students)->pluck('id')->map(fn ($id): int => (int) $id);
        $unmarked = $studentIds->filter(fn (int $studentId): bool => blank($this->attendance[$studentId] ?? null));

        if ($unmarked->isNotEmpty()) {
            Notification::make()
                ->title('Presensi belum lengkap')
                ->body("Masih ada {$unmarked->count()} siswa yang belum diberi status.")
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'selectedDate' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', Rule::in(['present', 'sick', 'permission', 'absent'])],
            'notes' => ['array'],
            'notes.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = CarbonImmutable::parse($this->selectedDate)->toDateString();
        $existingRecords = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        DB::transaction(function () use ($classId, $date, $studentIds, $existingRecords): void {
            foreach ($studentIds as $studentId) {
                $record = $existingRecords->get($studentId);
                $status = $this->attendance[$studentId];
                $data = [
                    'class_id' => $classId,
                    'recorded_by' => auth()->id(),
                    'status' => $status,
                    'is_late' => $status === 'present' && ($record?->is_late ?? false),
                    'source' => $record && $record->status === $status ? $record->source : 'teacher',
                    'notes' => filled($this->notes[$studentId] ?? null) ? trim($this->notes[$studentId]) : null,
                    'verified_at' => now(),
                ];

                if ($record) {
                    $record->update($data);

                    continue;
                }

                AttendanceRecord::create([
                    'student_id' => $studentId,
                    'attendance_date' => CarbonImmutable::parse($date),
                    ...$data,
                ]);
            }
        });

        $this->loadAttendance();

        Notification::make()
            ->title('Presensi kelas berhasil disimpan')
            ->body(count($this->students).' siswa telah tercatat untuk '.$this->formattedDate().'.')
            ->success()
            ->send();
    }

    public function approveSubmission(int $submissionId): void
    {
        $submission = $this->pendingSubmission($submissionId);
        abort_unless(ParentSubmissionResource::canReview($submission), 403);

        $submission->update(['status' => 'approved']);
        $this->loadAttendance();

        Notification::make()
            ->title('Informasi orang tua diterima')
            ->body("Presensi {$submission->student->name} otomatis diperbarui.")
            ->success()
            ->send();
    }

    public function rejectSubmission(int $submissionId): void
    {
        $submission = $this->pendingSubmission($submissionId);
        abort_unless(ParentSubmissionResource::canReview($submission), 403);

        $submission->update([
            'status' => 'rejected',
            'review_note' => 'Ditolak melalui halaman presensi kelas.',
        ]);
        $this->loadAttendance();

        Notification::make()
            ->title('Informasi orang tua ditolak')
            ->warning()
            ->send();
    }

    /** @return array<string, int> */
    public function summary(): array
    {
        $statuses = collect($this->attendance);

        return [
            'present' => $statuses->filter(fn ($status): bool => $status === 'present')->count(),
            'sick' => $statuses->filter(fn ($status): bool => $status === 'sick')->count(),
            'permission' => $statuses->filter(fn ($status): bool => $status === 'permission')->count(),
            'absent' => $statuses->filter(fn ($status): bool => $status === 'absent')->count(),
            'unrecorded' => $statuses->filter(fn ($status): bool => blank($status))->count(),
        ];
    }

    public function formattedDate(): string
    {
        return CarbonImmutable::parse($this->selectedDate)->translatedFormat('d F Y');
    }

    public function attendanceReportUrl(): string
    {
        return route('reports.class-attendance', [
            'class_id' => $this->selectedClassId,
            'date' => $this->selectedDate,
        ]);
    }

    private function loadAttendance(): void
    {
        $this->students = [];
        $this->attendance = [];
        $this->notes = [];
        $this->pendingSubmissions = [];

        if (! $this->selectedClassId) {
            return;
        }

        $classId = $this->authorizedClassId();
        $date = CarbonImmutable::parse($this->selectedDate)->toDateString();
        $records = AttendanceRecord::query()
            ->with(['arrival', 'parentSubmission'])
            ->where('class_id', $classId)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $this->pendingSubmissions = ParentSubmission::query()
            ->with(['student', 'parent.user'])
            ->whereHas('student', fn ($query) => $query->where('class_id', $classId))
            ->whereIn('type', ['sick', 'permission'])
            ->where('status', 'pending')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->latest()
            ->get()
            ->map(fn (ParentSubmission $submission): array => [
                'id' => $submission->id,
                'student_name' => $submission->student?->name ?? 'Siswa',
                'parent_name' => $submission->parent?->user?->name ?? 'Orang tua',
                'type' => $submission->type,
                'type_label' => $submission->type === 'sick' ? 'Sakit' : 'Izin',
                'description' => $submission->description,
                'date_label' => $submission->start_date->eq($submission->end_date)
                    ? $submission->start_date->format('d/m/Y')
                    : $submission->start_date->format('d/m/Y').' - '.$submission->end_date->format('d/m/Y'),
            ])
            ->all();

        foreach ($students as $student) {
            $record = $records->get($student->id);
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
                'source' => $record?->source,
                'source_label' => $this->sourceLabel($record),
                'source_detail' => $this->sourceDetail($record),
                'is_late' => $record?->is_late ?? false,
                'is_verified' => filled($record?->verified_at),
            ];
            $this->attendance[$student->id] = $record?->status;
            $this->notes[$student->id] = $record?->notes ?? '';
        }
    }

    private function authorizedClassId(): int
    {
        abort_unless($this->selectedClassId, 404);

        $classId = (int) $this->selectedClassId;
        abort_unless(auth()->user()->managedClasses()->whereKey($classId)->exists(), 403);

        return $classId;
    }

    private function pendingSubmission(int $submissionId): ParentSubmission
    {
        $classId = $this->authorizedClassId();

        return ParentSubmission::query()
            ->whereKey($submissionId)
            ->where('status', 'pending')
            ->whereHas('student', fn ($query) => $query->where('class_id', $classId))
            ->firstOrFail();
    }

    private function sourceLabel(?AttendanceRecord $record): string
    {
        return match ($record?->source) {
            'arrival' => 'Dari Loket',
            'parent_submission' => 'Laporan Orang Tua',
            'teacher' => 'Dicatat Guru',
            'manual' => 'Catatan Manual',
            default => 'Belum Dicatat',
        };
    }

    private function sourceDetail(?AttendanceRecord $record): ?string
    {
        if ($record?->arrival) {
            return 'Check-in pukul '.substr($record->arrival->arrival_time, 0, 5);
        }

        if ($record?->parentSubmission) {
            return $record->parentSubmission->title;
        }

        return $record?->verified_at ? 'Sudah diverifikasi' : null;
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Models\HomeActivity;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ActivityScoreService;
use App\Support\FixedActivityChecklist;
use App\Support\HomeActivityTemplate;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class DailyHomeActivities extends Page
{
    protected static ?string $title = 'Aktivitas Rumah';

    protected static ?string $navigationLabel = 'Aktivitas Rumah';

    protected static string|\UnitEnum|null $navigationGroup = 'Buku Penghubung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-home-work-o';

    protected static ?string $slug = 'aktivitas-rumah-harian';

    protected string $view = 'filament.pages.daily-home-activities';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $selectedDate = '';

    public ?int $selectedClassId = null;

    public ?int $selectedStudentId = null;

    /** @var array<int, array<string, mixed>> */
    public array $classes = [];

    /** @var array<int, array<string, mixed>> */
    public array $students = [];

    /** @var array<string, bool> */
    public array $checks = [];

    public string $note = '';

    /** @var array<int, array<string, mixed>> */
    public array $monitoring = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('view home activities') ?? false)
            && $user->hasAnyRole(['admin', 'guru', 'orang_tua']);
    }

    public function getSubheading(): ?string
    {
        return $this->isParent()
            ? 'Centang kebiasaan yang sudah dilakukan anak hari ini. Daftar aktivitas selalu tersedia dan tidak dapat diubah.'
            : 'Pantau pengisian aktivitas rumah oleh orang tua untuk setiap siswa di kelas.';
    }

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();

        if ($this->isParent()) {
            $this->students = auth()->user()
                ->accessibleStudents()
                ->with('class')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'class_name' => $student->class?->name ?? 'Belum ada kelas',
                    'grade_level' => $student->class?->grade_level,
                ])
                ->all();
            $this->selectedStudentId = $this->students[0]['id'] ?? null;
        } else {
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
        }

        $this->loadData();
    }

    public function updatedSelectedDate(): void
    {
        $this->validateOnly('selectedDate', [
            'selectedDate' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $this->loadData();
    }

    public function updatedSelectedStudentId(): void
    {
        $this->selectedStudentId = $this->selectedStudentId ? (int) $this->selectedStudentId : null;
        $this->loadData();
    }

    public function updatedSelectedClassId(): void
    {
        $this->selectedClassId = $this->selectedClassId ? (int) $this->selectedClassId : null;
        $this->loadData();
    }

    public function markAll(bool $checked): void
    {
        abort_unless($this->isParent(), 403);

        foreach (FixedActivityChecklist::items($this->template()) as $item) {
            $this->checks[$item['key']] = $checked;
        }
    }

    public function saveChecklist(): void
    {
        abort_unless($this->isParent(), 403);

        $this->validate([
            'selectedDate' => ['required', 'date', 'before_or_equal:today'],
            'selectedStudentId' => ['required', 'integer'],
            'checks' => ['array'],
            'checks.*' => ['boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = auth()->user()
            ->accessibleStudents()
            ->with('class')
            ->whereKey((int) $this->selectedStudentId)
            ->firstOrFail();
        $template = HomeActivityTemplate::forGrade($student->class?->grade_level);

        $date = CarbonImmutable::parse($this->selectedDate)->toDateString();
        $activity = HomeActivity::query()
            ->where('student_id', $student->id)
            ->whereDate('activity_date', $date)
            ->first();
        $data = [
            'parent_id' => $student->parent_id,
            'activity_groups' => FixedActivityChecklist::apply($template, $this->checks),
            'note' => filled($this->note) ? trim($this->note) : null,
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ];

        if ($activity) {
            $activity->update($data);
        } else {
            HomeActivity::query()->create([
                'student_id' => $student->id,
                'activity_date' => $date,
                ...$data,
            ]);
        }

        $this->loadData();

        Notification::make()
            ->title('Aktivitas rumah berhasil disimpan')
            ->body('Checklist '.$student->name.' untuk '.$this->formattedDate().' sudah diperbarui.')
            ->success()
            ->send();
    }

    public function isParent(): bool
    {
        return auth()->user()?->hasRole('orang_tua') ?? false;
    }

    /** @return array<int, array<string, mixed>> */
    public function template(): array
    {
        if ($this->isParent()) {
            $gradeLevel = collect($this->students)
                ->firstWhere('id', (int) $this->selectedStudentId)['grade_level'] ?? null;
        } else {
            $gradeLevel = collect($this->classes)
                ->firstWhere('id', (int) $this->selectedClassId)['grade_level'] ?? null;
        }

        return HomeActivityTemplate::forGrade($gradeLevel ? (int) $gradeLevel : null);
    }

    /** @return array{checked: int, total: int} */
    public function completion(): array
    {
        $items = FixedActivityChecklist::items($this->template());
        $checked = collect($items)
            ->filter(fn (array $item): bool => (bool) ($this->checks[$item['key']] ?? false))
            ->count();

        return ['checked' => $checked, 'total' => count($items)];
    }

    /** @return array{submitted: int, pending: int, total: int} */
    public function monitoringSummary(): array
    {
        $submitted = collect($this->monitoring)->where('submitted', true)->count();
        $total = count($this->monitoring);

        return [
            'submitted' => $submitted,
            'pending' => max(0, $total - $submitted),
            'total' => $total,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function weeklyScores(): array
    {
        if ($this->isParent() && ! $this->selectedStudentId) {
            return [];
        }

        if (! $this->isParent() && ! $this->selectedClassId) {
            return [];
        }

        $date = CarbonImmutable::parse($this->selectedDate);
        $from = $date->startOfWeek()->toDateString();
        $to = $date->endOfWeek()->toDateString();
        $query = HomeActivity::query()->whereBetween('activity_date', [$from, $to]);
        $studentCount = 1;

        if ($this->isParent()) {
            $query->where('student_id', (int) $this->selectedStudentId);
        } else {
            $classId = $this->authorizedClass()->id;
            $query->whereHas('student', fn ($studentQuery) => $studentQuery
                ->where('class_id', $classId)
                ->where('status', 'active'));
            $studentCount = max(1, count($this->monitoring));
        }

        return app(ActivityScoreService::class)->summarize(
            $query->get(),
            $this->template(),
            $from,
            $to,
            'home',
            $studentCount,
        );
    }

    public function formattedDate(): string
    {
        return CarbonImmutable::parse($this->selectedDate)->translatedFormat('l, d F Y');
    }

    private function loadData(): void
    {
        $this->checks = [];
        $this->note = '';
        $this->monitoring = [];

        if ($this->isParent()) {
            $this->loadParentChecklist();

            return;
        }

        $this->loadClassMonitoring();
    }

    private function loadParentChecklist(): void
    {
        if (! $this->selectedStudentId) {
            return;
        }

        $student = auth()->user()
            ->accessibleStudents()
            ->with('class')
            ->whereKey((int) $this->selectedStudentId)
            ->firstOrFail();
        $template = HomeActivityTemplate::forGrade($student->class?->grade_level);
        $record = HomeActivity::query()
            ->where('student_id', $student->id)
            ->whereDate('activity_date', $this->selectedDate)
            ->first();

        $this->checks = FixedActivityChecklist::checksFrom(
            $template,
            $record?->resolvedActivityGroups() ?? [],
        );
        $this->note = $record?->note ?? '';
    }

    private function loadClassMonitoring(): void
    {
        if (! $this->selectedClassId) {
            return;
        }

        $class = $this->authorizedClass();
        $template = HomeActivityTemplate::forGrade($class->grade_level);
        $totalActivities = count(FixedActivityChecklist::items($template));
        $records = HomeActivity::query()
            ->whereHas('student', fn ($query) => $query->where('class_id', $class->id))
            ->whereDate('activity_date', $this->selectedDate)
            ->get()
            ->keyBy('student_id');

        $this->monitoring = Student::query()
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) use ($records, $totalActivities): array {
                $record = $records->get($student->id);
                $checked = $record
                    ? collect($record->resolvedActivityGroups())
                        ->flatMap(fn (array $group) => $group['items'])
                        ->where('checked', true)
                        ->count()
                    : 0;

                return [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'initials' => str($student->name)
                        ->explode(' ')
                        ->filter()
                        ->take(2)
                        ->map(fn (string $part): string => str($part)->substr(0, 1)->upper()->toString())
                        ->join(''),
                    'checked' => $checked,
                    'total' => $totalActivities,
                    'submitted' => filled($record?->submitted_at),
                    'submitted_at' => $record?->submitted_at?->format('H:i'),
                    'record_url' => $record ? HomeActivityResource::getUrl('view', ['record' => $record]) : null,
                ];
            })
            ->all();
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

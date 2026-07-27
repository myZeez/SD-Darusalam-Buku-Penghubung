<?php

namespace App\Filament\Resources\StudentArrivals\Pages;

use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Models\Student;
use App\Models\StudentArrival;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class LoketArrival extends Page
{
    protected static string $resource = StudentArrivalResource::class;

    protected string $view = 'filament.resources.student-arrivals.pages.loket-arrival';

    public string $search = '';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->hasRole('loket') ?? false;
    }

    public function getTitle(): string
    {
        return 'Piket Keterlambatan';
    }

    public function getSubheading(): ?string
    {
        return 'Catat hanya siswa yang terlambat. Waktu, kelas, dan notifikasi wali kelas tersimpan otomatis.';
    }

    public function getUnrecordedStudentsProperty(): Collection
    {
        return Student::query()
            ->with('class')
            ->where('status', 'active')
            ->whereNotIn('id', StudentArrival::query()->whereDate('arrival_date', today())->select('student_id'))
            ->when(filled($this->search), fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')->orWhere('nis', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->get();
    }

    public function getLateStudentsProperty(): Collection
    {
        return StudentArrival::query()
            ->with(['student', 'schoolClass'])
            ->whereDate('arrival_date', today())
            ->where('status', 'late')
            ->when(filled($this->search), fn ($query) => $query->whereHas('student', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')->orWhere('nis', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('arrival_time')
            ->get();
    }

    public function markLate(int $studentId): void
    {
        $student = Student::query()->where('status', 'active')->findOrFail($studentId);

        StudentArrival::query()->firstOrCreate(
            ['student_id' => $student->id, 'arrival_date' => today()],
            ['recorded_by' => auth()->id()],
        );

        Notification::make()
            ->title($student->name.' tercatat terlambat')
            ->success()
            ->send();
    }
}

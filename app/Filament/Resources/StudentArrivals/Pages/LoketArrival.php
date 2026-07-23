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
        return 'Loket Kedatangan';
    }

    public function getSubheading(): ?string
    {
        return 'Cari siswa, lalu tekan Hadir. Waktu, kelas, dan status kedatangan disimpan otomatis.';
    }

    public function getWaitingStudentsProperty(): Collection
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

    public function getAttendedStudentsProperty(): Collection
    {
        return StudentArrival::query()
            ->with(['student', 'schoolClass'])
            ->whereDate('arrival_date', today())
            ->when(filled($this->search), fn ($query) => $query->whereHas('student', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')->orWhere('nis', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('arrival_time')
            ->get();
    }

    public function markPresent(int $studentId): void
    {
        $student = Student::query()->where('status', 'active')->findOrFail($studentId);

        StudentArrival::query()->firstOrCreate(
            ['student_id' => $student->id, 'arrival_date' => today()],
            ['recorded_by' => auth()->id()],
        );

        Notification::make()
            ->title($student->name.' tercatat hadir')
            ->success()
            ->send();
    }
}

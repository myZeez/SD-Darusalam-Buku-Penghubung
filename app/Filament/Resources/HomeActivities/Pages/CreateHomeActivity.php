<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Models\HomeActivity;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateHomeActivity extends CreateRecord
{
    protected static string $resource = HomeActivityResource::class;

    protected static bool $canCreateAnother = false;

    protected int $createdActivityCount = 0;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless(HomeActivityResource::canCreate(), 403);
        $classes = $user?->hasRole('guru') ? $user->managedClasses() : $user?->accessibleClasses();
        $classes?->findOrFail((int) $data['class_id']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $classId = (int) Arr::pull($data, 'class_id');
        $students = Student::query()
            ->where('class_id', $classId)
            ->orderBy('name')
            ->get(['id', 'parent_id']);

        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'data.class_id' => 'Kelas yang dipilih belum memiliki siswa.',
            ]);
        }

        $existingCount = HomeActivity::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('activity_date', $data['activity_date'])
            ->count();

        if ($existingCount > 0) {
            throw ValidationException::withMessages([
                'data.activity_date' => "Aktivitas rumah untuk {$existingCount} siswa pada tanggal ini sudah ada. Pilih tanggal lain agar checklist yang telah diisi tidak tertimpa.",
            ]);
        }

        $template = Arr::except($data, ['student_id', 'parent_id']);

        return DB::transaction(function () use ($students, $template): HomeActivity {
            $firstActivity = null;

            foreach ($students as $student) {
                $activity = HomeActivity::query()->create([
                    ...$template,
                    'student_id' => $student->id,
                    'parent_id' => $student->parent_id,
                ]);

                $firstActivity ??= $activity;
                $this->createdActivityCount++;
            }

            return $firstActivity;
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Aktivitas rumah berhasil diterapkan ke {$this->createdActivityCount} siswa";
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl();
    }
}

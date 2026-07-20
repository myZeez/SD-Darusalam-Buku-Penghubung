<?php

namespace App\Actions\Students;

use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveStudentWithFamily
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data): Student {
            $studentUser = $this->createStudentUser($data);
            $parent = $this->resolveParent($data);
            $classId = $this->resolveClassId($data);

            return Student::create([
                ...$this->studentData($data),
                'user_id' => $studentUser->id,
                'parent_id' => $parent->id,
                'class_id' => $classId,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data): Student {
            $studentUser = $student->user ?: new User;
            $studentUser->fill([
                'name' => $data['name'],
                'email' => $data['student_email'],
                'phone' => $data['student_phone'] ?? null,
                'status' => $data['status'],
            ]);

            if (filled($data['student_password'] ?? null)) {
                $studentUser->password = $data['student_password'];
            }

            $studentUser->save();
            $studentUser->syncRoles(['siswa']);

            $parent = $this->resolveParent($data);
            $classId = $this->resolveClassId($data, $student->id);

            $student->update([
                ...$this->studentData($data),
                'user_id' => $studentUser->id,
                'parent_id' => $parent->id,
                'class_id' => $classId,
            ]);

            return $student->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createStudentUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['student_email'],
            'phone' => $data['student_phone'] ?? null,
            'password' => $data['student_password'],
            'status' => $data['status'],
        ]);

        $user->assignRole('siswa');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveParent(array $data): ParentProfile
    {
        if (($data['parent_mode'] ?? 'existing') === 'existing') {
            return ParentProfile::query()->findOrFail($data['parent_id']);
        }

        $parentUser = User::create([
            'name' => $data['parent_name'],
            'email' => $data['parent_email'],
            'phone' => $data['parent_phone'] ?? null,
            'password' => $data['parent_password'],
            'status' => 'active',
        ]);
        $parentUser->assignRole('orang_tua');

        return ParentProfile::create([
            'user_id' => $parentUser->id,
            'father_name' => $data['father_name'] ?? null,
            'mother_name' => $data['mother_name'] ?? null,
            'phone' => $data['parent_phone'] ?? null,
            'address' => $data['parent_address'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function studentData(array $data): array
    {
        return Arr::only($data, [
            'nis',
            'name',
            'gender',
            'birth_date',
            'photo',
            'status',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveClassId(array $data, ?int $studentId = null): int
    {
        if (filled($data['class_id'] ?? null)) {
            $preferredClass = SchoolClass::query()
                ->lockForUpdate()
                ->findOrFail($data['class_id']);

            if ($this->classHasSpace($preferredClass, $studentId)) {
                return $preferredClass->id;
            }

            $gradeLevel = $preferredClass->grade_level;
            $excludedClassId = $preferredClass->id;
        } else {
            $gradeLevel = (int) ($data['grade_level'] ?? 0);
            $excludedClassId = null;
        }

        $fallbackClass = SchoolClass::query()
            ->when($excludedClassId, fn ($query) => $query->whereKeyNot($excludedClassId))
            ->where('grade_level', $gradeLevel)
            ->whereNotNull('teacher_id')
            ->lockForUpdate()
            ->get()
            ->filter(fn (SchoolClass $class): bool => $this->classHasSpace($class, $studentId))
            ->sortBy(fn (SchoolClass $class): float => $class->students()
                ->where('status', 'active')
                ->when($studentId, fn ($query) => $query->whereKeyNot($studentId))
                ->count() / max($class->capacity, 1))
            ->first();

        if (! $fallbackClass) {
            throw ValidationException::withMessages([
                filled($data['class_id'] ?? null) ? 'data.class_id' : 'data.grade_level' => 'Belum ada kelas tersedia pada tingkat yang dipilih.',
            ]);
        }

        return $fallbackClass->id;
    }

    private function classHasSpace(SchoolClass $class, ?int $studentId = null): bool
    {
        $studentCount = $class->students()
            ->where('status', 'active')
            ->when($studentId, fn ($query) => $query->whereKeyNot($studentId))
            ->count();

        return $studentCount < max($class->capacity, 1);
    }
}

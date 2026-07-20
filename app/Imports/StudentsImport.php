<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    public function __construct(private readonly ?User $actor = null)
    {
    }

    public function model(array $row)
    {
        if (blank($row['nis'] ?? null) || blank($row['name'] ?? null)) {
            return null;
        }

        $classId = filled($row['class_id'] ?? null) ? (int) $row['class_id'] : null;

        if ($this->actor?->hasRole('guru') && (! $classId || ! $this->actor->managedClasses()->whereKey($classId)->exists())) {
            throw ValidationException::withMessages([
                'file' => 'Impor hanya dapat memasukkan siswa ke kelas yang Anda kelola.',
            ]);
        }

        return new Student([
            'class_id' => $classId,
            'parent_id' => $row['parent_id'] ?? null,
            'nis' => $row['nis'],
            'name' => $row['name'],
            'gender' => $row['gender'] ?? null,
            'birth_date' => $row['birth_date'] ?? null,
            'status' => $row['status'] ?? 'active',
        ]);
    }
}

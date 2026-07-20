<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (blank($row['nis'] ?? null) || blank($row['name'] ?? null)) {
            return null;
        }

        return new Student([
            'class_id' => $row['class_id'] ?? null,
            'parent_id' => $row['parent_id'] ?? null,
            'nis' => $row['nis'],
            'name' => $row['name'],
            'gender' => $row['gender'] ?? null,
            'birth_date' => $row['birth_date'] ?? null,
            'status' => $row['status'] ?? 'active',
        ]);
    }
}

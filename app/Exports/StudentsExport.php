<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Student::query()
            ->with(['class', 'parent'])
            ->get()
            ->map(fn (Student $student) => [
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
                'birth_date' => optional($student->birth_date)->format('Y-m-d'),
                'class' => $student->class?->name,
                'parent' => $student->parent?->user?->name,
                'status' => $student->status,
            ]);
    }

    public function headings(): array
    {
        return [
            'nis',
            'name',
            'gender',
            'birth_date',
            'class',
            'parent',
            'status',
        ];
    }
}

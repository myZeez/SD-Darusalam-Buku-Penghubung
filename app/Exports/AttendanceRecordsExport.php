<?php

namespace App\Exports;

use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceRecordsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return AttendanceRecord::query()
            ->with(['student', 'schoolClass', 'recorder'])
            ->when($this->filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', '>=', $date))
            ->when($this->filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', '<=', $date))
            ->when($this->filters['class_id'] ?? null, fn (Builder $query, int|string $classId): Builder => $query->where('class_id', $classId))
            ->orderBy('attendance_date')
            ->orderBy('class_id')
            ->orderBy('student_id');
    }

    public function map($record): array
    {
        return [
            $record->attendance_date?->format('d/m/Y'),
            $record->student?->nis,
            $record->student?->name,
            $record->schoolClass?->name,
            match ($record->status) {
                'present' => 'Hadir',
                'sick' => 'Sakit',
                'permission' => 'Izin',
                'absent' => 'Alpa',
                default => $record->status,
            },
            $record->is_late ? 'Ya' : 'Tidak',
            match ($record->source) {
                'arrival' => 'Catatan Piket',
                'parent_submission' => 'Pengajuan Orang Tua',
                'teacher' => 'Wali Kelas',
                default => 'Manual',
            },
            $record->recorder?->name,
            $record->notes,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Status Kehadiran',
            'Terlambat',
            'Sumber Data',
            'Dikonfirmasi Oleh',
            'Catatan',
        ];
    }
}

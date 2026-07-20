<?php

namespace App\Exports;

use App\Models\StudentArrival;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentArrivalsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return StudentArrival::query()
            ->with(['student', 'schoolClass', 'recorder'])
            ->when($this->filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('arrival_date', '>=', $date))
            ->when($this->filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('arrival_date', '<=', $date))
            ->when($this->filters['class_id'] ?? null, fn (Builder $query, int|string $classId): Builder => $query->where('class_id', $classId))
            ->orderBy('arrival_date')
            ->orderBy('arrival_time');
    }

    public function map($arrival): array
    {
        return [
            $arrival->arrival_date?->format('d/m/Y'),
            substr($arrival->arrival_time, 0, 5),
            $arrival->student?->nis,
            $arrival->student?->name,
            $arrival->schoolClass?->name,
            $arrival->status === 'late' ? 'Terlambat' : 'Tepat Waktu',
            $arrival->recorder?->name ?? 'Sistem',
            $arrival->notes,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Waktu Datang',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Status Kedatangan',
            'Dicatat Oleh',
            'Catatan',
        ];
    }
}

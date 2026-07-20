<?php

namespace App\Models;

use App\Support\AcademicCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'academic_year',
        'semester',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AcademicPeriod $period): void {
            $duplicate = static::query()
                ->where('academic_year', $period->academic_year)
                ->where('semester', $period->semester)
                ->whereKeyNot($period->getKey())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'semester' => 'Semester tersebut sudah tersedia pada tahun ajaran yang dipilih.',
                ]);
            }

            if ($period->start_date && $period->end_date && $period->end_date->lt($period->start_date)) {
                throw ValidationException::withMessages([
                    'end_date' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
                ]);
            }
        });

        static::saved(function (AcademicPeriod $period): void {
            if ($period->is_active) {
                static::query()
                    ->whereKeyNot($period->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    public function getLabelAttribute(): string
    {
        return $this->academic_year.' - Semester '.AcademicCalendar::semesterLabel($this->semester);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function lessonPeriods()
    {
        return $this->hasMany(LessonPeriod::class);
    }
}

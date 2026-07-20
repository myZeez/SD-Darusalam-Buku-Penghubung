<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LessonPeriod extends Model
{
    protected $fillable = [
        'academic_period_id',
        'name',
        'sequence',
        'start_time',
        'end_time',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LessonPeriod $period): void {
            $duplicateSequence = static::query()
                ->where('academic_period_id', $period->academic_period_id)
                ->where('sequence', $period->sequence)
                ->whereKeyNot($period->getKey())
                ->exists();

            if ($duplicateSequence) {
                throw ValidationException::withMessages([
                    'sequence' => 'Nomor urut periode sudah digunakan pada periode akademik ini.',
                ]);
            }

            if ($period->start_time >= $period->end_time) {
                throw ValidationException::withMessages([
                    'end_time' => 'Jam selesai harus setelah jam mulai.',
                ]);
            }

            $overlap = static::query()
                ->where('academic_period_id', $period->academic_period_id)
                ->whereKeyNot($period->getKey())
                ->where('start_time', '<', $period->end_time)
                ->where('end_time', '>', $period->start_time)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_time' => 'Rentang waktu bertabrakan dengan periode lain.',
                ]);
            }
        });
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }
}

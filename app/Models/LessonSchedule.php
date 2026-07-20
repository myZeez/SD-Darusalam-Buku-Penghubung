<?php

namespace App\Models;

use App\Support\AcademicCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LessonSchedule extends Model
{
    protected $fillable = [
        'teaching_assignment_id',
        'lesson_period_id',
        'day_of_week',
        'room',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LessonSchedule $schedule): void {
            $assignment = TeachingAssignment::query()->find($schedule->teaching_assignment_id);
            $period = LessonPeriod::query()->find($schedule->lesson_period_id);

            if (! $assignment || ! $period) {
                return;
            }

            if ((int) $assignment->academic_period_id !== (int) $period->academic_period_id) {
                throw ValidationException::withMessages([
                    'lesson_period_id' => 'Periode jam harus berasal dari periode akademik yang sama.',
                ]);
            }

            if ($period->type !== 'lesson') {
                throw ValidationException::withMessages([
                    'lesson_period_id' => 'Jadwal pelajaran tidak dapat ditempatkan pada waktu istirahat atau kegiatan lain.',
                ]);
            }

            if (! $schedule->is_active) {
                return;
            }

            $baseQuery = static::query()
                ->whereKeyNot($schedule->getKey())
                ->where('day_of_week', $schedule->day_of_week)
                ->where('lesson_period_id', $schedule->lesson_period_id)
                ->where('is_active', true);

            $classConflict = (clone $baseQuery)
                ->whereHas('teachingAssignment', fn ($query) => $query
                    ->where('academic_period_id', $assignment->academic_period_id)
                    ->where('class_id', $assignment->class_id))
                ->exists();

            if ($classConflict) {
                throw ValidationException::withMessages([
                    'teaching_assignment_id' => 'Kelas sudah memiliki pelajaran pada hari dan periode tersebut.',
                ]);
            }

            $teacherConflict = (clone $baseQuery)
                ->whereHas('teachingAssignment', fn ($query) => $query
                    ->where('academic_period_id', $assignment->academic_period_id)
                    ->where('teacher_id', $assignment->teacher_id))
                ->exists();

            if ($teacherConflict) {
                throw ValidationException::withMessages([
                    'teaching_assignment_id' => 'Guru sudah mengajar kelas lain pada hari dan periode tersebut.',
                ]);
            }

            if (filled($schedule->room)) {
                $roomConflict = (clone $baseQuery)
                    ->where('room', $schedule->room)
                    ->whereHas('teachingAssignment', fn ($query) => $query
                        ->where('academic_period_id', $assignment->academic_period_id))
                    ->exists();

                if ($roomConflict) {
                    throw ValidationException::withMessages([
                        'room' => 'Ruangan sudah digunakan pada hari dan periode tersebut.',
                    ]);
                }
            }
        });
    }

    public function getDayLabelAttribute(): string
    {
        return AcademicCalendar::dayLabel($this->day_of_week);
    }

    public function teachingAssignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function lessonPeriod()
    {
        return $this->belongsTo(LessonPeriod::class);
    }
}

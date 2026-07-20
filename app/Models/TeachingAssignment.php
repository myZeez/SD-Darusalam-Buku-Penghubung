<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TeachingAssignment extends Model
{
    protected $fillable = [
        'academic_period_id',
        'teacher_id',
        'class_id',
        'subject_id',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TeachingAssignment $assignment): void {
            $duplicate = static::query()
                ->where('academic_period_id', $assignment->academic_period_id)
                ->where('teacher_id', $assignment->teacher_id)
                ->where('class_id', $assignment->class_id)
                ->where('subject_id', $assignment->subject_id)
                ->whereKeyNot($assignment->getKey())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'subject_id' => 'Penugasan Guru, kelas, dan mata pelajaran tersebut sudah tersedia.',
                ]);
            }

            $classPeriodId = SchoolClass::query()
                ->whereKey($assignment->class_id)
                ->value('academic_period_id');

            if ($classPeriodId && (int) $classPeriodId !== (int) $assignment->academic_period_id) {
                throw ValidationException::withMessages([
                    'class_id' => 'Kelas harus berada pada periode akademik yang sama.',
                ]);
            }
        });
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }
}

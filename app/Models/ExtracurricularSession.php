<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularSession extends Model
{
    protected $fillable = [
        'extracurricular_id',
        'teacher_id',
        'session_date',
        'title',
        'start_time',
        'end_time',
        'material',
        'notes',
        'photo',
        'coach_attendance_status',
        'coach_notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExtracurricularSession $session): void {
            $session->teacher_id ??= $session->extracurricular?->teacher_id;
        });

        static::created(fn (ExtracurricularSession $session) => $session->syncEnrolledStudents());
    }

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function attendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class);
    }

    public function syncEnrolledStudents(): void
    {
        $studentIds = $this->extracurricular
            ->enrollments()
            ->where('status', 'active')
            ->pluck('student_id');

        foreach ($studentIds as $studentId) {
            $this->attendances()->firstOrCreate(
                ['student_id' => $studentId],
                ['status' => 'present'],
            );
        }
    }
}

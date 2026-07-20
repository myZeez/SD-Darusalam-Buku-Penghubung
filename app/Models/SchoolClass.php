<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'teacher_id',
        'assistant_teacher_id',
        'academic_period_id',
        'name',
        'grade_level',
        'academic_year',
        'capacity',
        'room',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'capacity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SchoolClass $class): void {
            if ($class->academic_period_id) {
                $class->academic_year = AcademicPeriod::query()
                    ->whereKey($class->academic_period_id)
                    ->value('academic_year') ?? $class->academic_year;
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

    public function assistantTeacher()
    {
        return $this->belongsTo(Teacher::class, 'assistant_teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function arrivals()
    {
        return $this->hasMany(StudentArrival::class, 'class_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'class_id');
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class, 'class_id');
    }
}

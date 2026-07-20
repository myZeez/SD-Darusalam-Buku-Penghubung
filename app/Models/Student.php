<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'class_id',
        'parent_id',
        'nis',
        'name',
        'gender',
        'birth_date',
        'photo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (Student $student): void {
            if (! $student->wasChanged('parent_id')) {
                return;
            }

            $student->homeActivities()->update([
                'parent_id' => $student->parent_id,
            ]);
        });
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function schoolActivities()
    {
        return $this->hasMany(SchoolActivity::class);
    }

    public function homeActivities()
    {
        return $this->hasMany(HomeActivity::class);
    }

    public function arrivals()
    {
        return $this->hasMany(StudentArrival::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function parentSubmissions()
    {
        return $this->hasMany(ParentSubmission::class);
    }

    public function extracurricularEnrollments()
    {
        return $this->hasMany(ExtracurricularEnrollment::class);
    }

    public function extracurriculars()
    {
        return $this->belongsToMany(Extracurricular::class, 'extracurricular_enrollments')
            ->withPivot(['joined_at', 'status'])
            ->withTimestamps();
    }

    public function extracurricularAttendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class);
    }

    public function extracurricularScores()
    {
        return $this->hasMany(ExtracurricularScore::class);
    }
}

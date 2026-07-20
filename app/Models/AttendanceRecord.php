<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'arrival_id',
        'parent_submission_id',
        'recorded_by',
        'attendance_date',
        'status',
        'is_late',
        'source',
        'notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'is_late' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AttendanceRecord $record): void {
            if ($record->student_id) {
                $record->class_id = Student::query()->whereKey($record->student_id)->value('class_id');
            }

            $record->recorded_by ??= auth()->id();
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function arrival()
    {
        return $this->belongsTo(StudentArrival::class, 'arrival_id');
    }

    public function parentSubmission()
    {
        return $this->belongsTo(ParentSubmission::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

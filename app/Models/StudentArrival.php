<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class StudentArrival extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'recorded_by',
        'arrival_date',
        'arrival_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'arrival_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentArrival $arrival): void {
            $settings = SchoolSetting::current();
            $timezone = $settings->timezone;
            $now = CarbonImmutable::now($timezone);

            if (auth()->user()?->hasRole('loket')) {
                $arrival->arrival_date = $now->toDateString();
                $arrival->recorded_by = auth()->id();
                $arrival->status = 'late';
            }

            $arrival->arrival_date ??= $now->toDateString();
            $arrival->arrival_time ??= $now->format('H:i:s');
            $arrival->recorded_by ??= auth()->id();
            $arrival->class_id = Student::query()->whereKey($arrival->student_id)->value('class_id');

            $arrivalMoment = CarbonImmutable::parse(
                $arrival->arrival_date->format('Y-m-d').' '.$arrival->arrival_time,
                $timezone,
            );

            if (! auth()->user()?->hasRole('loket')) {
                $arrival->status = $arrivalMoment->greaterThan($settings->arrivalDeadline($arrival->arrival_date))
                    ? 'late'
                    : 'on_time';
            }
        });

        static::saved(function (StudentArrival $arrival): void {
            AttendanceRecord::query()->updateOrCreate(
                [
                    'student_id' => $arrival->student_id,
                    'attendance_date' => $arrival->arrival_date,
                ],
                [
                    'class_id' => $arrival->class_id,
                    'arrival_id' => $arrival->id,
                    'recorded_by' => $arrival->recorded_by,
                    'status' => 'present',
                    'is_late' => $arrival->status === 'late',
                    'source' => 'arrival',
                    'verified_at' => null,
                ],
            );

            $arrival->notifyTeacherWhenLate();
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

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function attendance()
    {
        return $this->hasOne(AttendanceRecord::class, 'arrival_id');
    }

    private function notifyTeacherWhenLate(): void
    {
        $this->loadMissing('student.class.teacher');
        $teacherUserIds = collect([
            $this->student?->class?->teacher?->user_id,
        ])->filter()->unique();

        if ($teacherUserIds->isEmpty()) {
            return;
        }

        $title = sprintf(
            'Siswa Terlambat: %s (%s)',
            $this->student->name,
            $this->arrival_date->format('d/m/Y'),
        );

        if ($this->status !== 'late') {
            UserNotification::query()
                ->whereIn('user_id', $teacherUserIds)
                ->where('title', $title)
                ->delete();

            return;
        }

        foreach ($teacherUserIds as $teacherUserId) {
            UserNotification::query()->updateOrCreate(
                [
                    'user_id' => $teacherUserId,
                    'title' => $title,
                ],
                [
                    'created_by' => $this->recorded_by,
                    'message' => sprintf(
                        '%s dari %s tercatat datang pukul %s.',
                        $this->student->name,
                        $this->student->class?->name ?? 'kelas belum ditentukan',
                        substr($this->arrival_time, 0, 5),
                    ),
                    'is_read' => false,
                ],
            );
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Extracurricular extends Model
{
    protected $fillable = [
        'teacher_id',
        'name',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'location',
        'capacity',
        'status',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ExtracurricularEnrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->hasMany(ExtracurricularEnrollment::class)->where('status', 'active');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'extracurricular_enrollments')
            ->withPivot(['joined_at', 'status'])
            ->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(ExtracurricularSession::class);
    }

    public function scores()
    {
        return $this->hasMany(ExtracurricularScore::class);
    }

    public function monitoredEnrollments(User $user): Collection
    {
        return $this->activeEnrollments()
            ->whereIn('student_id', $user->accessibleStudents()->select('students.id'))
            ->with('student.class')
            ->get();
    }

    /** @return array{total: int, present: int, missed: int, latest_status: ?string, latest_date: ?string} */
    public function attendanceOverviewFor(User $user): array
    {
        $query = ExtracurricularAttendance::query()
            ->join('extracurricular_sessions as monitored_sessions', 'monitored_sessions.id', '=', 'extracurricular_attendances.extracurricular_session_id')
            ->where('monitored_sessions.extracurricular_id', $this->getKey())
            ->whereIn('extracurricular_attendances.student_id', $user->accessibleStudents()->select('students.id'));

        $total = (clone $query)->count();
        $present = (clone $query)->where('extracurricular_attendances.status', 'present')->count();
        $latest = (clone $query)
            ->select('extracurricular_attendances.*', 'monitored_sessions.session_date as monitored_session_date')
            ->latest('monitored_sessions.session_date')
            ->latest('extracurricular_attendances.id')
            ->first();

        return [
            'total' => $total,
            'present' => $present,
            'missed' => max(0, $total - $present),
            'latest_status' => $latest?->status,
            'latest_date' => $latest?->monitored_session_date,
        ];
    }
}

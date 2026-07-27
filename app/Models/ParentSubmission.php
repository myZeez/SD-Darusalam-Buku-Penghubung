<?php

namespace App\Models;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class ParentSubmission extends Model
{
    protected $fillable = [
        'parent_id',
        'student_id',
        'reviewed_by',
        'type',
        'title',
        'description',
        'start_date',
        'end_date',
        'early_leave_time',
        'attachment',
        'status',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'early_leave_time' => 'string',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ParentSubmission $submission): void {
            $submission->parent_id = Student::query()
                ->whereKey($submission->student_id)
                ->value('parent_id');

            $submission->end_date ??= $submission->start_date;

            if ($submission->isDirty('status') && $submission->status !== 'pending') {
                $submission->reviewed_by = auth()->id() ?? $submission->reviewed_by;
                $submission->reviewed_at = now();
            }
        });

        static::saved(function (ParentSubmission $submission): void {
            $submission->syncAttendanceRecords();

            if ($submission->wasRecentlyCreated && $submission->status === 'pending') {
                $submission->notifyTeacherOfNewReport();
            }

            if ($submission->wasChanged('status') && $submission->status !== 'pending') {
                $submission->notifyParentOfReview();
                $submission->markTeacherNotificationAsRead();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function earlyDepartures()
    {
        return $this->hasMany(EarlyDeparture::class);
    }

    private function syncAttendanceRecords(): void
    {
        if ($this->status === 'approved' && $this->type === 'early_leave') {
            EarlyDeparture::query()->updateOrCreate(
                [
                    'student_id' => $this->student_id,
                    'departure_date' => $this->start_date->toDateString(),
                ],
                [
                    'class_id' => $this->student?->class_id,
                    'parent_submission_id' => $this->id,
                    'recorded_by' => $this->reviewed_by,
                    'departure_time' => $this->early_leave_time,
                    'notes' => $this->description,
                ],
            );

            return;
        }

        if ($this->status !== 'approved' || ! in_array($this->type, ['sick', 'permission'], true)) {
            if ($this->status === 'rejected') {
                $this->attendanceRecords()->where('source', 'parent_submission')->delete();
                $this->earlyDepartures()->delete();
            }

            return;
        }

        foreach (CarbonPeriod::create($this->start_date, $this->end_date ?? $this->start_date) as $date) {
            AttendanceRecord::query()->updateOrCreate(
                [
                    'student_id' => $this->student_id,
                    'attendance_date' => $date->toDateString(),
                ],
                [
                    'class_id' => $this->student?->class_id,
                    'parent_submission_id' => $this->id,
                    'recorded_by' => $this->reviewed_by,
                    'status' => $this->type,
                    'is_late' => false,
                    'source' => 'parent_submission',
                    'notes' => $this->description,
                    'verified_at' => $this->reviewed_at ?? now(),
                ],
            );
        }
    }

    private function notifyTeacherOfNewReport(): void
    {
        $this->loadMissing(['student.class.teacher', 'parent.user']);
        $teacherUserIds = $this->teacherUserIds();

        if ($teacherUserIds->isEmpty()) {
            return;
        }

        foreach ($teacherUserIds as $teacherUserId) {
            UserNotification::create([
                'user_id' => $teacherUserId,
                'created_by' => $this->parent?->user_id,
                'title' => $this->teacherNotificationTitle(),
                'message' => sprintf(
                    '%s mengirim laporan %s untuk %s pada %s. %s',
                    $this->parent?->user?->name ?? 'Orang tua',
                    $this->typeLabel(),
                    $this->student->name,
                    $this->dateRangeLabel(),
                    $this->title,
                ),
                'is_read' => false,
            ]);
        }
    }

    private function notifyParentOfReview(): void
    {
        $this->loadMissing(['student', 'parent']);
        $parentUserId = $this->parent?->user_id;

        if (! $parentUserId) {
            return;
        }

        $approved = $this->status === 'approved';
        UserNotification::create([
            'user_id' => $parentUserId,
            'created_by' => $this->reviewed_by,
            'title' => ($approved ? 'Pengajuan Disetujui: ' : 'Pengajuan Ditolak: ').$this->title,
            'message' => sprintf(
                'Laporan %s untuk %s telah %s.%s',
                $this->typeLabel(),
                $this->student?->name ?? 'siswa',
                $approved ? 'disetujui' : 'ditolak',
                filled($this->review_note) ? ' Catatan sekolah: '.$this->review_note : '',
            ),
            'is_read' => false,
        ]);
    }

    private function markTeacherNotificationAsRead(): void
    {
        $this->loadMissing('student.class.teacher');
        $teacherUserIds = $this->teacherUserIds();

        if ($teacherUserIds->isEmpty()) {
            return;
        }

        UserNotification::query()
            ->whereIn('user_id', $teacherUserIds)
            ->where('title', $this->teacherNotificationTitle())
            ->update(['is_read' => true]);
    }

    private function teacherUserIds()
    {
        return collect([
            $this->student?->class?->teacher?->user_id,
        ])->filter()->unique()->values();
    }

    private function teacherNotificationTitle(): string
    {
        return sprintf('Laporan Orang Tua: %s', $this->student?->name ?? 'Siswa');
    }

    private function typeLabel(): string
    {
        return match ($this->type) {
            'sick' => 'sakit',
            'permission' => 'izin',
            'early_leave' => 'izin pulang cepat',
            'family' => 'keperluan keluarga',
            'late' => 'keterlambatan',
            'home_report' => 'dari rumah',
            default => 'lainnya',
        };
    }

    private function dateRangeLabel(): string
    {
        $start = $this->start_date->format('d/m/Y');
        $end = ($this->end_date ?? $this->start_date)->format('d/m/Y');

        return $start === $end ? $start : "{$start} sampai {$end}";
    }
}

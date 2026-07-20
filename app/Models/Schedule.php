<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'class_id',
        'created_by',
        'title',
        'description',
        'location',
        'activity_date',
        'start_time',
        'end_time',
        'equipment',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Schedule $schedule): void {
            $schedule->notifyParentsWhenCreatedByTeacher();
        });
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(ScheduleResponse::class);
    }

    private function notifyParentsWhenCreatedByTeacher(): void
    {
        $creator = $this->creator;

        if (blank($this->class_id) || ! $creator?->hasRole('guru')) {
            return;
        }

        $className = $this->schoolClass?->name ?? 'kelas terkait';
        $message = sprintf(
            'Guru kelas membagikan agenda "%s" untuk %s pada %s.',
            $this->title,
            $className,
            $this->activity_date?->format('d/m/Y') ?? '-',
        );

        User::query()
            ->whereHas('parentProfile.students', fn (Builder $query): Builder => $query
                ->where('class_id', $this->class_id)
                ->where('status', 'active'))
            ->pluck('users.id')
            ->each(fn (int $userId) => UserNotification::query()->create([
                'user_id' => $userId,
                'created_by' => $creator->id,
                'title' => 'Agenda Baru: '.$this->title,
                'message' => $message,
            ]));
    }
}

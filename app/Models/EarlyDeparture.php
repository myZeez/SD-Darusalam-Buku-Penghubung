<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class EarlyDeparture extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'parent_submission_id',
        'recorded_by',
        'departure_date',
        'departure_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EarlyDeparture $departure): void {
            $departure->class_id = Student::query()->whereKey($departure->student_id)->value('class_id');
            $departure->recorded_by ??= auth()->id();
            $departure->departure_date ??= today();
            $departure->departure_time ??= CarbonImmutable::now(SchoolSetting::current()->timezone)->format('H:i:s');
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

    public function parentSubmission()
    {
        return $this->belongsTo(ParentSubmission::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

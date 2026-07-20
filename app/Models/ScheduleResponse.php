<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleResponse extends Model
{
    protected $fillable = [
        'schedule_id',
        'user_id',
        'response',
        'proposed_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'proposed_date' => 'date',
        ];
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

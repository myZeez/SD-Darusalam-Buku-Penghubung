<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularScore extends Model
{
    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'recorded_by',
        'title',
        'score',
        'assessed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'assessed_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExtracurricularScore $score): void {
            $score->recorded_by ??= auth()->id();
        });
    }

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

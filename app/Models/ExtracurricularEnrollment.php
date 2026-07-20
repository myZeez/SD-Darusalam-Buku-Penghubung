<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularEnrollment extends Model
{
    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'joined_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
        ];
    }

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

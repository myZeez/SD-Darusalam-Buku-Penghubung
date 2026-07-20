<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularAttendance extends Model
{
    protected $fillable = [
        'extracurricular_session_id',
        'student_id',
        'status',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(ExtracurricularSession::class, 'extracurricular_session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

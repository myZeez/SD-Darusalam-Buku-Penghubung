<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'nip',
        'gender',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function assistedClasses()
    {
        return $this->hasMany(SchoolClass::class, 'assistant_teacher_id');
    }

    public function schoolActivities()
    {
        return $this->hasMany(SchoolActivity::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function getClassDutySummaryAttribute(): string
    {
        $this->loadMissing(['classes', 'assistedClasses.teacher.user']);

        $primaryDuties = $this->classes
            ->sortBy('name')
            ->map(fn (SchoolClass $class): string => "Guru Utama {$class->name}");
        $assistantDuties = $this->assistedClasses
            ->sortBy('name')
            ->map(fn (SchoolClass $class): string => sprintf(
                'Guru Pendamping %s — mendampingi %s',
                $class->name,
                $class->teacher?->user?->name ?? 'guru utama',
            ));

        return collect()
            ->concat($primaryDuties)
            ->concat($assistantDuties)
            ->join(' • ') ?: 'Belum ditetapkan pada kelas';
    }
}

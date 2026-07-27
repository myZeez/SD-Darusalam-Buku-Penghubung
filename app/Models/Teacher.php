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

    public function schoolActivities()
    {
        return $this->hasMany(SchoolActivity::class);
    }

    public function getClassDutySummaryAttribute(): string
    {
        $this->loadMissing('classes');

        return $this->classes
            ->sortBy('name')
            ->map(fn (SchoolClass $class): string => "Wali Kelas {$class->name}")
            ->join(' | ') ?: 'Belum ditetapkan sebagai wali kelas';
    }
}

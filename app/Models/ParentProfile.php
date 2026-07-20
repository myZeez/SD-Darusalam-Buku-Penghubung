<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'father_name',
        'mother_name',
        'phone',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public function homeActivities()
    {
        return $this->hasMany(HomeActivity::class, 'parent_id');
    }

    public function submissions()
    {
        return $this->hasMany(ParentSubmission::class, 'parent_id');
    }
}

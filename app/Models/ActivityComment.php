<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityComment extends Model
{
    protected $table = 'comments';

    protected $fillable = [
        'parent_id',
        'activity_type',
        'activity_id',
        'user_id',
        'comment',
    ];

    public function activity()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parentComment()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function latestReply()
    {
        return $this->hasOne(self::class, 'parent_id')->latestOfMany();
    }

    public function threadRoot(): self
    {
        return $this->parentComment ?? $this;
    }
}

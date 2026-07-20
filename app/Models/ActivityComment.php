<?php

namespace App\Models;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
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

    protected static function booted(): void
    {
        static::created(function (self $comment): void {
            $comment->notifyConversationRecipient();
        });
    }

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

    private function notifyConversationRecipient(): void
    {
        $sender = $this->user;
        $activity = $this->activity;

        if (! $sender || ! $activity) {
            return;
        }

        $activity->loadMissing([
            'student.parent.user',
            'student.class.teacher.user',
        ]);

        if ($activity instanceof SchoolActivity) {
            $activity->loadMissing('teacher.user');
        }

        $recipient = match (true) {
            $activity instanceof SchoolActivity && $sender->hasRole('orang_tua') => $activity->teacher?->user ?? $activity->student?->class?->teacher?->user,
            $activity instanceof SchoolActivity => $activity->student?->parent?->user,
            $activity instanceof HomeActivity && $sender->hasRole('orang_tua') => $activity->student?->class?->teacher?->user,
            $activity instanceof HomeActivity => $activity->student?->parent?->user,
            default => null,
        };

        if (! $recipient || $recipient->is($sender)) {
            return;
        }

        $activityLabel = $activity instanceof SchoolActivity ? 'laporan sekolah' : 'laporan rumah';
        $studentName = $activity->student?->name ?? 'siswa terkait';
        $thread = $this->threadRoot();

        UserNotification::create([
            'user_id' => $recipient->id,
            'created_by' => $sender->id,
            'title' => 'Tanggapan Baru dari '.$sender->name,
            'message' => "Ada tanggapan baru pada {$activityLabel} {$studentName}.",
            'action_url' => ActivityCommentResource::getUrl('view', ['record' => $thread]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\ActivityComments\Schemas;

use App\Models\ActivityComment;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ActivityCommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.resources.activity-comments.thread')
                ->viewData(function (ActivityComment $record, $livewire): array {
                    $thread = $record->threadRoot();
                    $thread->load([
                        'activity.student.class.teacher.user',
                        'activity.student.parent.user',
                        'user.roles',
                        'replies.activity',
                        'replies.user.roles',
                    ]);

                    return [
                        'replyingToId' => $livewire->replyingToId,
                        'thread' => $thread,
                    ];
                })
                ->columnSpanFull(),
        ]);
    }
}

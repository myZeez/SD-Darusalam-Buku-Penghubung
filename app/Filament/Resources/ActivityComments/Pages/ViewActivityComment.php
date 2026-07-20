<?php

namespace App\Filament\Resources\ActivityComments\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Models\ActivityComment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityComment extends ViewRecord
{
    protected static string $resource = ActivityCommentResource::class;

    public string $replyMessage = '';

    public ?int $replyingToId = null;

    public function selectReply(int $messageId): void
    {
        $message = $this->resolveThreadMessage($messageId);

        abort_unless(ActivityCommentResource::canCreate() && ActivityCommentResource::canView($message->threadRoot()), 403);

        $this->replyingToId = $message->getKey();
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
    }

    public function sendReply(): void
    {
        abort_unless(ActivityCommentResource::canCreate() && ActivityCommentResource::canView($this->record->threadRoot()), 403);

        $data = $this->validate([
            'replyMessage' => ['required', 'string', 'max:5000'],
        ], attributes: [
            'replyMessage' => 'pesan balasan',
        ]);

        ActivityCommentResource::replyTo($this->record, trim($data['replyMessage']));

        $this->reset('replyMessage', 'replyingToId');
        $this->record->refresh();

        Notification::make()
            ->title('Pesan berhasil dikirim')
            ->success()
            ->send();
    }

    public function editMessageAction(): Action
    {
        return Action::make('editMessage')
            ->label('Ubah Pesan')
            ->icon('gmdi-edit-o')
            ->modalHeading('Ubah Pesan')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->fillForm(function (array $arguments): array {
                $message = $this->resolveThreadMessage((int) ($arguments['message'] ?? 0));

                abort_unless(ActivityCommentResource::canEdit($message), 403);

                return ['comment' => $message->comment];
            })
            ->form([
                Textarea::make('comment')
                    ->label('Isi Pesan')
                    ->rows(6)
                    ->maxLength(5000)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $message = $this->resolveThreadMessage((int) ($arguments['message'] ?? 0));

                abort_unless(ActivityCommentResource::canEdit($message), 403);

                $message->update(['comment' => trim($data['comment'])]);
                $message->threadRoot()->touch();
                $this->record->refresh();

                Notification::make()
                    ->title('Pesan berhasil diubah')
                    ->success()
                    ->send();
            });
    }

    private function resolveThreadMessage(int $messageId): ActivityComment
    {
        $root = $this->record->threadRoot();

        return ActivityComment::query()
            ->whereKey($messageId)
            ->where(function ($query) use ($root): void {
                $query
                    ->whereKey($root->getKey())
                    ->orWhere('parent_id', $root->getKey());
            })
            ->firstOrFail();
    }
}

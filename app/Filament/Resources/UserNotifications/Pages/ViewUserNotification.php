<?php

namespace App\Filament\Resources\UserNotifications\Pages;

use App\Filament\Resources\UserNotifications\UserNotificationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserNotification extends ViewRecord
{
    protected static string $resource = UserNotificationResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->user_id === auth()->id() && ! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openRelated')
                ->label('Buka Percakapan')
                ->icon('gmdi-forum-o')
                ->url(fn (): ?string => $this->record->action_url)
                ->visible(fn (): bool => filled($this->record->action_url)),
            EditAction::make()
                ->visible(fn (): bool => UserNotificationResource::canEdit($this->record)),
        ];
    }
}

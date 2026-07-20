<?php

namespace App\Filament\Resources\UserNotifications\Pages;

use App\Filament\Resources\UserNotifications\UserNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserNotification extends EditRecord
{
    protected static string $resource = UserNotificationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless(UserNotificationResource::canEdit($this->record), 403);
        abort_unless(UserNotificationResource::canNotifyUser((int) $data['user_id']), 403);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => UserNotificationResource::canDelete($this->record)),
        ];
    }
}

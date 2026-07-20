<?php

namespace App\Filament\Resources\UserNotifications\Pages;

use App\Filament\Resources\UserNotifications\UserNotificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserNotification extends CreateRecord
{
    protected static string $resource = UserNotificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage notifications'), 403);
        abort_unless(UserNotificationResource::canNotifyUser((int) $data['user_id']), 403);

        $data['created_by'] = $user->id;
        $data['is_read'] = false;

        return $data;
    }
}

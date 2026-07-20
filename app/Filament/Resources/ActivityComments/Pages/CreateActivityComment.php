<?php

namespace App\Filament\Resources\ActivityComments\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityComment extends CreateRecord
{
    protected static string $resource = ActivityCommentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage comments'), 403);
        abort_unless($user->canAccessActivity($data['activity_type'], (int) $data['activity_id']), 403);

        $data['user_id'] = $user->id;

        return $data;
    }
}

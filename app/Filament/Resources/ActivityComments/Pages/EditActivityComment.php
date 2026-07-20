<?php

namespace App\Filament\Resources\ActivityComments\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditActivityComment extends EditRecord
{
    protected static string $resource = ActivityCommentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage comments'), 403);
        abort_unless($user->canAccessActivity($data['activity_type'], (int) $data['activity_id']), 403);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => ActivityCommentResource::canDelete($this->record)),
        ];
    }
}

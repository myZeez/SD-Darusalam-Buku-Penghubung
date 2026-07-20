<?php

namespace App\Filament\Resources\ActivityComments\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityComment extends CreateRecord
{
    protected static string $resource = ActivityCommentResource::class;

    public function mount(): void
    {
        parent::mount();

        $activityType = request()->string('activity_type')->toString();
        $activityId = request()->integer('activity_id');
        $user = auth()->user();

        if ($activityId < 1 || ! in_array($activityType, [\App\Models\SchoolActivity::class, \App\Models\HomeActivity::class], true) || ! $user?->canAccessActivity($activityType, $activityId)) {
            return;
        }

        $this->form->fillPartially([
            'activity_type' => $activityType,
            'activity_id' => $activityId,
        ], ['activity_type', 'activity_id']);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage comments'), 403);
        abort_unless($user->canAccessActivity($data['activity_type'], (int) $data['activity_id']), 403);

        $data['user_id'] = $user->id;

        return $data;
    }
}

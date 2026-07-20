<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Filament\Resources\HomeActivities\Schemas\HomeActivityForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeActivity extends EditRecord
{
    protected static string $resource = HomeActivityResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['activity_groups'] = $this->record->resolvedActivityGroups()
            ?: HomeActivityForm::defaultActivityGroups();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage home activities'), 403);
        $student = $user->accessibleStudents()->findOrFail((int) $data['student_id']);
        abort_unless($student->parent_id, 403);

        $data['parent_id'] = $student->parent_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => HomeActivityResource::canDelete($this->record)),
        ];
    }
}

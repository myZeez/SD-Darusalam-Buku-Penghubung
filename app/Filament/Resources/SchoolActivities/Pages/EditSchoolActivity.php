<?php

namespace App\Filament\Resources\SchoolActivities\Pages;

use App\Filament\Resources\SchoolActivities\Schemas\SchoolActivityForm;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolActivity extends EditRecord
{
    protected static string $resource = SchoolActivityResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['activity_groups'] = $this->record->resolvedActivityGroups()
            ?: SchoolActivityForm::defaultActivityGroups();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage school activities'), 403);
        abort_unless($user->canAccessStudent((int) $data['student_id']), 403);

        if (! $user->isAdmin()) {
            abort_unless($user->teacher, 403);
            $data['teacher_id'] = $user->teacher->id;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => SchoolActivityResource::canDelete($this->record)),
        ];
    }
}

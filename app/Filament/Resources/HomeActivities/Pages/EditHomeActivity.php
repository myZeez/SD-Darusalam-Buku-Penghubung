<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Support\ActivityGroups;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeActivity extends EditRecord
{
    protected static string $resource = HomeActivityResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['activity_groups'] = $this->record->resolvedActivityGroups();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        abort_unless(HomeActivityResource::canEdit($this->record), 403);

        if ($user?->hasRole('orang_tua')) {
            $data['student_id'] = $this->record->student_id;
            $data['parent_id'] = $this->record->parent_id;
            $data['activity_date'] = $this->record->activity_date;
            $data['activity_groups'] = $this->mergeParentChecklist($data['activity_groups'] ?? []);
            $data['photo'] = $this->record->photo;

            return $data;
        }

        $students = $user?->hasRole('guru') ? $user->managedStudents() : $user?->accessibleStudents();
        $student = $students?->findOrFail((int) $data['student_id']);
        abort_unless($student->parent_id, 403);

        $data['parent_id'] = $student->parent_id;
        $data['activity_groups'] = $this->record->resolvedActivityGroups();
        $data['note'] = $this->record->note;
        $data['photo'] = $this->record->photo;

        return $data;
    }

    /** @param array<int|string, mixed> $submittedGroups */
    private function preserveChecklistStatus(array $submittedGroups): array
    {
        $groups = ActivityGroups::normalize($submittedGroups);
        $existingGroups = $this->record->resolvedActivityGroups();

        foreach ($groups as $groupIndex => &$group) {
            foreach ($group['items'] as $itemIndex => &$item) {
                $item['type'] = 'checklist';
                $item['text'] = '';
                $item['checked'] = (bool) ($existingGroups[$groupIndex]['items'][$itemIndex]['checked'] ?? false);
            }
            unset($item);
        }
        unset($group);

        return $groups;
    }

    /** @param array<int|string, mixed> $submittedGroups */
    private function mergeParentChecklist(array $submittedGroups): array
    {
        $submittedGroups = array_values(ActivityGroups::normalize($submittedGroups));
        $groups = $this->record->resolvedActivityGroups();

        foreach ($groups as $groupIndex => &$group) {
            $submittedItems = array_values($submittedGroups[$groupIndex]['items'] ?? []);

            foreach ($group['items'] as $itemIndex => &$item) {
                if (($item['type'] ?? 'checklist') !== 'checklist') {
                    continue;
                }

                if (array_key_exists('checked', $submittedItems[$itemIndex] ?? [])) {
                    $item['checked'] = (bool) $submittedItems[$itemIndex]['checked'];
                }
            }
            unset($item);
        }
        unset($group);

        return $groups;
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

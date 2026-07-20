<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeActivity extends CreateRecord
{
    protected static string $resource = HomeActivityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless(HomeActivityResource::canCreate(), 403);
        $students = $user?->hasRole('guru') ? $user->managedStudents() : $user?->accessibleStudents();
        $student = $students?->findOrFail((int) $data['student_id']);
        abort_unless($student->parent_id, 403);

        $data['parent_id'] = $student->parent_id;

        return $data;
    }
}

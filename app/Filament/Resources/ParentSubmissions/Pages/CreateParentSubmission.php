<?php

namespace App\Filament\Resources\ParentSubmissions\Pages;

use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateParentSubmission extends CreateRecord
{
    protected static string $resource = ParentSubmissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless(ParentSubmissionResource::canCreate(), 403);

        $student = $user->accessibleStudents()->findOrFail((int) $data['student_id']);
        abort_unless($student->parent?->user_id === $user->id, 403);

        $data['parent_id'] = $student->parent_id;
        $data['status'] = 'pending';
        $data['title'] = sprintf(
            '%s - %s',
            ($data['type'] ?? null) === 'sick' ? 'Sakit' : 'Izin',
            $student->name,
        );

        return $data;
    }
}

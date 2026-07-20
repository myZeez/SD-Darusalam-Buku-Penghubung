<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\TeachingAssignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateTeachingAssignment extends CreateRecord
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected int $savedAssignmentCount = 0;

    protected function handleRecordCreation(array $data): Model
    {
        $subjectIds = array_values(array_unique(array_map(
            'intval',
            Arr::pull($data, 'subject_ids', []),
        )));

        return DB::transaction(function () use ($data, $subjectIds): TeachingAssignment {
            $firstAssignment = null;

            foreach ($subjectIds as $subjectId) {
                $assignment = TeachingAssignment::query()->updateOrCreate(
                    [
                        ...Arr::only($data, ['academic_period_id', 'teacher_id', 'class_id']),
                        'subject_id' => $subjectId,
                    ],
                    Arr::only($data, ['is_active', 'notes']),
                );

                $firstAssignment ??= $assignment;
                $this->savedAssignmentCount++;
            }

            return $firstAssignment;
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "{$this->savedAssignmentCount} penugasan mata pelajaran berhasil disimpan";
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl();
    }
}

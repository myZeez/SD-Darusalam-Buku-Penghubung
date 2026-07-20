<?php

namespace App\Filament\Resources\SchoolActivities\Pages;

use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Models\SchoolActivity;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateSchoolActivity extends CreateRecord
{
    protected static string $resource = SchoolActivityResource::class;

    public function mount(): void
    {
        parent::mount();

        $studentId = request()->integer('student_id');
        $user = auth()->user();

        if ($studentId < 1 || ! $user?->canAccessStudent($studentId)) {
            return;
        }

        $this->form->fillPartially([
            'student_id' => $studentId,
        ], ['student_id']);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException $exception) {
            $dailyReportExists = SchoolActivity::query()
                ->where('student_id', $data['student_id'])
                ->whereDate('activity_date', $data['activity_date'])
                ->exists();

            if (! $dailyReportExists) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'data.activity_date' => SchoolActivity::DUPLICATE_DAILY_REPORT_MESSAGE,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
}

<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user?->can('manage schedules'), 403);

        if ($user->hasRole('guru')) {
            abort_unless(filled($data['class_id'] ?? null), 403);
            abort_unless($user->managedClasses()->whereKey($data['class_id'])->exists(), 403);
        }

        $data['created_by'] = $user->id;

        return $data;
    }
}

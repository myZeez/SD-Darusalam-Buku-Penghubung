<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        abort_unless(ScheduleResource::canEdit($this->record), 403);

        if ($user?->hasRole('guru')) {
            abort_unless(filled($data['class_id'] ?? null), 403);
            abort_unless($user->managedClasses()->whereKey($data['class_id'])->exists(), 403);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => ScheduleResource::canDelete($this->record)),
        ];
    }
}

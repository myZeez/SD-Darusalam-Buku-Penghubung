<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTeachingAssignment extends EditRecord
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn (): bool => TeachingAssignmentResource::canDelete($this->record)),
        ];
    }
}

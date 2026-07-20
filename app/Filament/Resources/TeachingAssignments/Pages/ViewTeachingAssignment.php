<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTeachingAssignment extends ViewRecord
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->visible(fn (): bool => TeachingAssignmentResource::canEdit($this->record))];
    }
}

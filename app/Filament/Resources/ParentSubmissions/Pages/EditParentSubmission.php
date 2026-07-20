<?php

namespace App\Filament\Resources\ParentSubmissions\Pages;

use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParentSubmission extends EditRecord
{
    protected static string $resource = ParentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => ParentSubmissionResource::canDelete($this->record)),
        ];
    }
}

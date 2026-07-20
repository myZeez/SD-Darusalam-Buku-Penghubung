<?php

namespace App\Filament\Resources\SchoolActivities\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\SchoolActivities\SchoolActivityResource;
use App\Models\SchoolActivity;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchoolActivity extends ViewRecord
{
    protected static string $resource = SchoolActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('comment')
                ->label('Tanggapi')
                ->icon('gmdi-forum-o')
                ->url(fn (): string => ActivityCommentResource::getUrl('create', [
                    'activity_type' => SchoolActivity::class,
                    'activity_id' => $this->record->id,
                ]))
                ->visible(fn (): bool => ActivityCommentResource::canCreate()),
            EditAction::make()
                ->visible(fn (): bool => SchoolActivityResource::canEdit($this->record)),
        ];
    }
}

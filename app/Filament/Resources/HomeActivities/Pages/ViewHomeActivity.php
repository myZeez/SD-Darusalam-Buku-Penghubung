<?php

namespace App\Filament\Resources\HomeActivities\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Models\HomeActivity;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHomeActivity extends ViewRecord
{
    protected static string $resource = HomeActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('comment')
                ->label('Tanggapi')
                ->icon('gmdi-forum-o')
                ->url(fn (): string => ActivityCommentResource::getUrl('create', [
                    'activity_type' => HomeActivity::class,
                    'activity_id' => $this->record->id,
                ]))
                ->visible(fn (): bool => ActivityCommentResource::canCreate()),
            EditAction::make()
                ->visible(fn (): bool => HomeActivityResource::canEdit($this->record)),
        ];
    }
}

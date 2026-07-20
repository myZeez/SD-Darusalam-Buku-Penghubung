<?php

namespace App\Filament\Resources\ParentSubmissions\Pages;

use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewParentSubmission extends ViewRecord
{
    protected static string $resource = ParentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui')
                ->icon('gmdi-check-circle-o')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'pending'
                    && ParentSubmissionResource::canReview($this->record))
                ->action(function (): void {
                    abort_unless(ParentSubmissionResource::canReview($this->record), 403);
                    $this->record->update(['status' => 'approved']);
                    $this->refreshFormData(['status', 'reviewed_by', 'reviewed_at']);
                }),
            Action::make('reject')
                ->label('Tolak')
                ->icon('gmdi-cancel-o')
                ->color('danger')
                ->form([
                    Textarea::make('review_note')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                ->visible(fn (): bool => $this->record->status === 'pending'
                    && ParentSubmissionResource::canReview($this->record))
                ->action(function (array $data): void {
                    abort_unless(ParentSubmissionResource::canReview($this->record), 403);
                    $this->record->update([
                        'status' => 'rejected',
                        'review_note' => $data['review_note'],
                    ]);
                    $this->refreshFormData(['status', 'review_note', 'reviewed_by', 'reviewed_at']);
                }),
            EditAction::make()
                ->visible(fn (): bool => ParentSubmissionResource::canEdit($this->record)),
        ];
    }
}

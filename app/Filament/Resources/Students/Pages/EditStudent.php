<?php

namespace App\Filament\Resources\Students\Pages;

use App\Actions\Students\SaveStudentWithFamily;
use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('editParent')
                ->label('Ubah Data Orang Tua')
                ->icon('heroicon-o-user-group')
                ->url(fn (): string => ParentProfileResource::getUrl('edit', ['record' => $this->record->parent_id]))
                ->visible(fn (): bool => (auth()->user()?->isAdmin() ?? false) && filled($this->record->parent_id)),
            DeleteAction::make()
                ->visible(fn (): bool => StudentResource::canDelete($this->record)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('user');

        return [
            ...$data,
            'student_email' => $this->record->user?->email,
            'student_phone' => $this->record->user?->phone,
            'parent_mode' => 'existing',
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless(StudentResource::canManageClass((int) $data['class_id']), 403);

        return app(SaveStudentWithFamily::class)->update(
            $record,
            $data,
            allowClassFallback: auth()->user()?->isAdmin() ?? false,
        );
    }
}

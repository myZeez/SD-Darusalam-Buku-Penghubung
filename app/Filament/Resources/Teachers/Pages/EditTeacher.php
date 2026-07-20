<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Ubah Profil' : 'Ubah Guru';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => TeacherResource::canDelete($this->record)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('user');

        return [
            ...$data,
            'profile_name' => $this->record->user?->name,
            'profile_email' => $this->record->user?->email,
            'profile_phone' => $this->record->user?->phone,
            'profile_avatar' => $this->record->user?->avatar,
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record->user->update([
                'name' => $data['profile_name'],
                'email' => $data['profile_email'],
                'phone' => $data['profile_phone'] ?? null,
                'avatar' => $data['profile_avatar'] ?? null,
            ]);

            $record->update(Arr::only($data, ['nip', 'gender', 'address']));

            return $record->refresh();
        });
    }
}

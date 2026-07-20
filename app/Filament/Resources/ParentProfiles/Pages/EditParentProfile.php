<?php

namespace App\Filament\Resources\ParentProfiles\Pages;

use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditParentProfile extends EditRecord
{
    protected static string $resource = ParentProfileResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('orang_tua') ? 'Ubah Profil Orang Tua' : 'Ubah Orang Tua';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('user');

        return [
            ...$data,
            'profile_name' => $this->record->user?->name,
            'profile_email' => $this->record->user?->email,
            'profile_phone' => $this->record->user?->phone ?? $this->record->phone,
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

            $record->update([
                ...Arr::only($data, ['father_name', 'mother_name', 'address']),
                'phone' => $data['profile_phone'] ?? null,
            ]);

            return $record->refresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => ParentProfileResource::canDelete($this->record)),
        ];
    }
}

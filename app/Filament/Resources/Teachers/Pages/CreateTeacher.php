<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Teacher {
            $user = User::create([
                'name' => $data['profile_name'],
                'email' => $data['profile_email'],
                'phone' => $data['profile_phone'] ?? null,
                'avatar' => $data['profile_avatar'] ?? null,
                'password' => $data['profile_password'],
                'status' => 'active',
            ]);
            $user->assignRole('guru');

            return Teacher::create([
                'user_id' => $user->id,
                ...Arr::only($data, ['nip', 'gender', 'address']),
            ]);
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Guru dan akun login berhasil dibuat';
    }
}

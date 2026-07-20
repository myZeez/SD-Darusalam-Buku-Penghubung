<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Waktu Verifikasi Email'),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel(),
                TextInput::make('job_title')
                    ->label('Jabatan')
                    ->maxLength(255)
                    ->placeholder('Contoh: Kepala Sekolah atau Wakil Kesiswaan')
                    ->helperText('Terutama digunakan untuk akun Admin. Jabatan tidak mengubah hak akses.'),
                FileUpload::make('avatar')
                    ->label('Foto Profil')
                    ->image()
                    ->directory('avatars'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->required()
                    ->default('active'),
                Select::make('roles')
                    ->label('Peran')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->whereIn('name', User::BUSINESS_ROLES),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record): string => User::roleLabel($record->name))
                    ->multiple()
                    ->maxItems(1)
                    ->required()
                    ->helperText('Gunakan menu Siswa dan Orang Tua untuk membuat akun keluarga yang saling terhubung.')
                    ->preload(),
            ]);
    }
}

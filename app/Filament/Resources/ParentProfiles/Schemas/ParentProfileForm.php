<?php

namespace App\Filament\Resources\ParentProfiles\Schemas;

use App\Models\ParentProfile;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class ParentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Akun Orang Tua')
                    ->description('Hubungkan profil keluarga dengan akun pengguna berperan sebagai orang tua.')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->role('orang_tua'),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Akun Pengguna'),
                    ]),
                Section::make('Profil Pribadi')
                    ->description('Informasi akun yang digunakan orang tua untuk masuk ke buku penghubung.')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        FileUpload::make('profile_avatar')
                            ->label('Foto Profil')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->columnSpanFull(),
                        TextInput::make('profile_name')
                            ->label('Nama Akun Orang Tua')
                            ->required(),
                        TextInput::make('profile_email')
                            ->label('Alamat Email')
                            ->email()
                            ->rules(fn (?ParentProfile $record): array => [
                                Rule::unique(User::class, 'email')->ignore($record?->user_id),
                            ])
                            ->required(),
                        TextInput::make('profile_phone')
                            ->label('Nomor Telepon')
                            ->tel(),
                    ]),
                Section::make('Data Keluarga')
                    ->description('Data ayah, ibu, dan alamat keluarga yang terhubung dengan siswa.')
                    ->icon(Heroicon::OutlinedHome)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('father_name')
                            ->label('Nama Ayah'),
                        TextInput::make('mother_name')
                            ->label('Nama Ibu'),
                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        Textarea::make('address')
                            ->label('Alamat Keluarga')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

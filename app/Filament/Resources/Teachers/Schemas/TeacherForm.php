<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Models\Teacher;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil dan Akun Guru')
                    ->description(fn (string $operation): string => $operation === 'create'
                        ? 'Akun login guru dibuat otomatis bersama data kepegawaiannya.'
                        : 'Informasi yang tampil pada profil dan akun guru.')
                    ->icon(Heroicon::OutlinedUserCircle)
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
                            ->label('Nama Guru')
                            ->required(),
                        TextInput::make('profile_email')
                            ->label('Alamat Email')
                            ->email()
                            ->rules(fn (?Teacher $record): array => [
                                Rule::unique(User::class, 'email')->ignore($record?->user_id),
                            ])
                            ->required(),
                        TextInput::make('profile_phone')
                            ->label('Nomor Telepon')
                            ->tel(),
                        TextInput::make('profile_password')
                            ->label('Kata Sandi')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required()
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('profile_password_confirmation')
                            ->label('Ulangi Kata Sandi')
                            ->password()
                            ->revealable()
                            ->same('profile_password')
                            ->required()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                    ]),
                Section::make('Data Kepegawaian')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP')
                            ->unique(ignoreRecord: true),
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ]),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

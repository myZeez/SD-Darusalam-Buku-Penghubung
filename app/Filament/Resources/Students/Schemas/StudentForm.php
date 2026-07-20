<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'xl' => 2,
                ])
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Identitas Siswa')
                            ->description('Data akademik dan identitas utama siswa.')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->columns([
                                'md' => 2,
                            ])
                            ->schema([
                                TextInput::make('nis')
                                    ->label('NIS')
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Nama Siswa')
                                    ->required(),
                                Select::make('class_id')
                                    ->relationship(
                                        name: 'class',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->withCount([
                                            'students' => fn (Builder $query): Builder => $query->where('status', 'active'),
                                        ]),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (SchoolClass $record): string => sprintf(
                                        '%s - Tingkat %s (%d/%d siswa)',
                                        $record->name,
                                        $record->grade_level ?? '-',
                                        $record->students_count ?? $record->students()->where('status', 'active')->count(),
                                        $record->capacity,
                                    ))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Kelas Tujuan')
                                    ->helperText('Admin memilih kelas tujuan. Jika penuh, sistem memindahkan siswa ke kelas lain pada tingkat yang sama.'),
                                Select::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'male' => 'Laki-laki',
                                        'female' => 'Perempuan',
                                    ])
                                    ->required(),
                                DatePicker::make('birth_date')
                                    ->label('Tanggal Lahir'),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Aktif',
                                        'inactive' => 'Tidak Aktif',
                                    ])
                                    ->required()
                                    ->default('active'),
                                FileUpload::make('photo')
                                    ->label('Foto Siswa')
                                    ->image()
                                    ->directory('students')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Akun Siswa')
                            ->description('Akun ini digunakan siswa untuk melihat buku penghubungnya.')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->columns([
                                'md' => 2,
                            ])
                            ->schema([
                                TextInput::make('student_email')
                                    ->label('Email Siswa')
                                    ->email()
                                    ->rules(fn (?Student $record): array => [
                                        Rule::unique(User::class, 'email')->ignore($record?->user_id),
                                    ])
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('student_phone')
                                    ->label('Nomor Telepon Siswa')
                                    ->tel()
                                    ->columnSpanFull(),
                                TextInput::make('student_password')
                                    ->label('Kata Sandi')
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->required(fn (string $operation, ?Student $record): bool => $operation === 'create' || blank($record?->user_id)),
                                TextInput::make('student_password_confirmation')
                                    ->label('Ulangi Kata Sandi')
                                    ->password()
                                    ->revealable()
                                    ->same('student_password')
                                    ->required(fn (string $operation, Get $get, ?Student $record): bool => $operation === 'create' || blank($record?->user_id) || filled($get('student_password')))
                                    ->dehydrated(false),
                            ]),
                    ]),
                Section::make('Hubungkan Orang Tua')
                    ->description('Gunakan akun keluarga yang sudah ada atau buat akun orang tua bersama akun siswa.')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columnSpanFull()
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        ToggleButtons::make('parent_mode')
                            ->label('Sumber Data Orang Tua')
                            ->options([
                                'existing' => 'Orang Tua Terdaftar',
                                'new' => 'Buat Orang Tua Baru',
                            ])
                            ->icons([
                                'existing' => Heroicon::OutlinedMagnifyingGlass,
                                'new' => Heroicon::OutlinedUserPlus,
                            ])
                            ->grouped()
                            ->inline()
                            ->live()
                            ->default('existing')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('parent_id')
                            ->label('Akun Orang Tua')
                            ->options(fn (): array => ParentProfile::query()
                                ->with('user')
                                ->get()
                                ->mapWithKeys(fn (ParentProfile $parent): array => [
                                    $parent->id => sprintf(
                                        '%s - %s',
                                        $parent->user?->name ?? $parent->father_name ?? 'Orang Tua',
                                        $parent->user?->email ?? 'tanpa email',
                                    ),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'existing')
                            ->required(fn (Get $get): bool => $get('parent_mode') === 'existing')
                            ->dehydratedWhenHidden(false)
                            ->columnSpanFull(),
                        TextInput::make('parent_name')
                            ->label('Nama Akun Orang Tua')
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false),
                        TextInput::make('parent_email')
                            ->label('Email Orang Tua')
                            ->email()
                            ->unique(User::class, 'email')
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false),
                        TextInput::make('parent_phone')
                            ->label('Nomor Telepon Orang Tua')
                            ->tel()
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false),
                        TextInput::make('parent_password')
                            ->label('Kata Sandi Orang Tua')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false),
                        TextInput::make('parent_password_confirmation')
                            ->label('Ulangi Kata Sandi Orang Tua')
                            ->password()
                            ->revealable()
                            ->same('parent_password')
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydrated(false),
                        TextInput::make('father_name')
                            ->label('Nama Ayah')
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false),
                        TextInput::make('mother_name')
                            ->label('Nama Ibu')
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false),
                        Textarea::make('parent_address')
                            ->label('Alamat Keluarga')
                            ->rows(4)
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                            ->dehydratedWhenHidden(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

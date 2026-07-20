<?php

namespace App\Filament\Pages\Auth;

use App\Actions\Students\SaveStudentWithFamily;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class RegisterStudent extends Register
{
    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 2])
                ->schema([
                    Section::make('Data dan Akun Siswa')
                        ->description('Akun siswa digunakan untuk melihat presensi, jadwal, dan buku penghubung.')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->columns(['md' => 2])
                        ->schema([
                            TextInput::make('nis')
                                ->label('NIS')
                                ->unique(Student::class, 'nis')
                                ->required(),
                            TextInput::make('name')
                                ->label('Nama Lengkap Siswa')
                                ->required(),
                            TextInput::make('email')
                                ->label('Email Login Siswa')
                                ->email()
                                ->unique(User::class, 'email')
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('student_phone')
                                ->label('Nomor Telepon Siswa')
                                ->tel(),
                            Select::make('grade_level')
                                ->label('Tingkat Kelas')
                                ->options([
                                    1 => 'Kelas 1',
                                    2 => 'Kelas 2',
                                    3 => 'Kelas 3',
                                    4 => 'Kelas 4',
                                    5 => 'Kelas 5',
                                    6 => 'Kelas 6',
                                ])
                                ->helperText('Sistem memilih kelas yang masih tersedia pada tingkat ini.')
                                ->required(),
                            Select::make('gender')
                                ->label('Jenis Kelamin')
                                ->options([
                                    'male' => 'Laki-laki',
                                    'female' => 'Perempuan',
                                ])
                                ->required(),
                            DatePicker::make('birth_date')
                                ->label('Tanggal Lahir')
                                ->maxDate(today())
                                ->required(),
                            TextInput::make('password')
                                ->label('Kata Sandi Keluarga')
                                ->password()
                                ->revealable()
                                ->rule(Password::default())
                                ->same('passwordConfirmation')
                                ->helperText('Kata sandi ini digunakan untuk akun siswa dan orang tua.')
                                ->required(),
                            TextInput::make('passwordConfirmation')
                                ->label('Ulangi Kata Sandi')
                                ->password()
                                ->revealable()
                                ->dehydrated(false)
                                ->required(),
                        ]),
                    Section::make('Data dan Akun Orang Tua')
                        ->description('Buat akun orang tua baru atau hubungkan anak ke akun keluarga yang sudah ada.')
                        ->icon(Heroicon::OutlinedUserGroup)
                        ->columns(['md' => 2])
                        ->schema([
                            ToggleButtons::make('parent_mode')
                                ->label('Akun Orang Tua')
                                ->options([
                                    'new' => 'Buat Akun Baru',
                                    'existing' => 'Sudah Punya Akun',
                                ])
                                ->icons([
                                    'new' => Heroicon::OutlinedUserPlus,
                                    'existing' => Heroicon::OutlinedLink,
                                ])
                                ->default('new')
                                ->grouped()
                                ->live()
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('parent_name')
                                ->label('Nama Akun Orang Tua')
                                ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                                ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                            TextInput::make('parent_email')
                                ->label('Email Login Orang Tua')
                                ->email()
                                ->different('email')
                                ->rules(fn (Get $get): array => $get('parent_mode') === 'new'
                                    ? [Rule::unique(User::class, 'email')]
                                    : [Rule::exists(User::class, 'email')])
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('parent_password')
                                ->label('Password Akun Orang Tua Saat Ini')
                                ->password()
                                ->revealable()
                                ->helperText('Digunakan hanya untuk memastikan akun keluarga benar-benar milik Anda.')
                                ->visible(fn (Get $get): bool => $get('parent_mode') === 'existing')
                                ->required(fn (Get $get): bool => $get('parent_mode') === 'existing')
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                            TextInput::make('parent_phone')
                                ->label('Nomor Telepon Orang Tua')
                                ->tel()
                                ->visible(fn (Get $get): bool => $get('parent_mode') === 'new')
                                ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
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
                                ->required(fn (Get $get): bool => $get('parent_mode') === 'new')
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        $parentMode = $data['parent_mode'] ?? 'new';
        $existingParent = $parentMode === 'existing'
            ? $this->resolveExistingParent($data)
            : null;

        $studentData = [
            'nis' => $data['nis'],
            'name' => $data['name'],
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'],
            'grade_level' => $data['grade_level'],
            'status' => 'active',
            'student_email' => $data['email'],
            'student_phone' => $data['student_phone'] ?? null,
            'student_password' => $data['password'],
            'parent_mode' => $parentMode,
        ];

        if ($existingParent) {
            $studentData['parent_id'] = $existingParent->id;
        } else {
            $studentData += [
                'parent_name' => $data['parent_name'],
                'parent_email' => $data['parent_email'],
                'parent_phone' => $data['parent_phone'],
                'parent_password' => $data['password'],
                'father_name' => $data['father_name'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'parent_address' => $data['parent_address'],
            ];
        }

        $student = app(SaveStudentWithFamily::class)->create($studentData);

        session()->flash('registration_credentials', [
            'student_name' => $student->name,
            'student_email' => $student->user->email,
            'parent_name' => $student->parent->user->name,
            'parent_email' => $student->parent->user->email,
            'password' => $data['password'],
            'student_password' => $data['password'],
            'parent_password' => $existingParent ? null : $data['password'],
            'parent_uses_existing_password' => filled($existingParent),
        ]);

        return $student->user;
    }

    /** @param array<string, mixed> $data */
    private function resolveExistingParent(array $data): ParentProfile
    {
        $user = User::query()
            ->where('email', $data['parent_email'])
            ->where('status', 'active')
            ->with(['roles', 'parentProfile'])
            ->first();

        if (! $user?->hasRole('orang_tua') || ! $user->parentProfile || ! Hash::check($data['parent_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.parent_password' => 'Email atau password akun orang tua tidak sesuai.',
            ]);
        }

        return $user->parentProfile;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Registrasi Siswa';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Buat Akun Siswa dan Orang Tua';
    }

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label('Daftar dan Buat Akun Keluarga');
    }
}

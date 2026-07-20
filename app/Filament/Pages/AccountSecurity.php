<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class AccountSecurity extends Page
{
    use InteractsWithSchemas;

    protected static ?string $title = 'Keamanan Akun';

    protected static ?string $navigationLabel = 'Keamanan Akun';

    protected static string|\UnitEnum|null $navigationGroup = 'Informasi';

    protected static ?int $navigationSort = 99;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-password-o';

    protected static ?string $slug = 'keamanan-akun';

    protected string $view = 'filament.pages.account-security';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->hasAnyRole(['guru', 'orang_tua', 'siswa']) ?? false);
    }

    public static function getNavigationBadge(): ?string
    {
        return auth()->user()?->must_change_password ? '!' : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->must_change_password
            ? 'Password sementara harus diganti sebelum melanjutkan menggunakan aplikasi.'
            : 'Perbarui password akun Anda secara mandiri.';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Ganti Password')
                    ->description('Gunakan minimal 8 karakter dan jangan berikan password kepada orang lain.')
                    ->icon('gmdi-lock-o')
                    ->columns(['md' => 2])
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->currentPassword()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->same('password_confirmation')
                            ->required(),
                        TextInput::make('password_confirmation')
                            ->label('Ulangi Password Baru')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(),
                    ]),
            ]);
    }

    public function changePassword(): void
    {
        $data = $this->form->getState();
        auth()->user()->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Password berhasil diperbarui')
            ->success()
            ->send();
    }
}

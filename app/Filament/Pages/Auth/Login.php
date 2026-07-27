<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function registerAction(): Action
    {
        return parent::registerAction()->label('Registrasi Siswa');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Masuk';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'SIMPATI — Mendekatkan komunikasi, memantau perkembangan';
    }
}

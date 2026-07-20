<?php

namespace App\Filament\Pages\Auth;

use App\Models\PasswordResetRequest;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class RequestPasswordHelp extends RequestPasswordReset
{
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();
        $user = User::query()
            ->where('email', $data['email'])
            ->where('status', 'active')
            ->first();

        if ($user?->hasAnyRole(['siswa', 'orang_tua'])) {
            $student = $user->hasRole('siswa')
                ? $user->student
                : $user->parentProfile?->students()
                    ->whereNotNull('class_id')
                    ->with('class.teacher')
                    ->first();

            PasswordResetRequest::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'status' => 'pending',
                ],
                [
                    'student_id' => $student?->id,
                    'teacher_id' => $student?->class?->teacher?->user_id,
                    'request_note' => $data['request_note'] ?? null,
                ],
            );
        }

        Notification::make()
            ->title('Permintaan diterima')
            ->body('Jika akun ditemukan, admin akan memprosesnya dan password sementara disampaikan melalui wali kelas.')
            ->success()
            ->send();

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->label('Email Akun Siswa atau Orang Tua')
                ->email()
                ->autocomplete()
                ->autofocus()
                ->required(),
            Textarea::make('request_note')
                ->label('Keterangan')
                ->placeholder('Opsional, misalnya nama siswa dan kelas.')
                ->rows(3),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Lupa Kata Sandi';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Minta Bantuan Kata Sandi';
    }

    protected function getRequestFormAction(): Action
    {
        return parent::getRequestFormAction()
            ->label('Kirim Permintaan ke Admin');
    }
}

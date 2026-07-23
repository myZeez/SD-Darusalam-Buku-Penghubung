<?php

namespace App\Actions\Auth;

use App\Models\PasswordResetRequest;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessPasswordResetRequest
{
    public function handle(PasswordResetRequest $passwordResetRequest): string
    {
        return DB::transaction(function () use ($passwordResetRequest): string {
            $request = PasswordResetRequest::query()
                ->with(['user.roles', 'student.class.teacher'])
                ->lockForUpdate()
                ->findOrFail($passwordResetRequest->id);

            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Permintaan ini sudah pernah diproses.',
                ]);
            }

            $teacherId = $request->teacher_id ?? $request->student?->class?->teacher?->user_id;

            if (! $teacherId) {
                throw ValidationException::withMessages([
                    'teacher' => 'Wali kelas belum tersedia. Tetapkan kelas dan wali kelas terlebih dahulu.',
                ]);
            }

            $temporaryPassword = (string) config('school.default_login_password');
            $request->user->update([
                'password' => $temporaryPassword,
                'must_change_password' => true,
            ]);
            $request->update([
                'teacher_id' => $teacherId,
                'processed_by' => auth()->id(),
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            UserNotification::create([
                'user_id' => $teacherId,
                'created_by' => auth()->id(),
                'title' => 'Password Default: '.($request->student?->name ?? $request->user->name),
                'message' => sprintf(
                    'Sampaikan secara pribadi kepada %s. Email login: %s. Password default: %s. Pengguna wajib mengganti password setelah login.',
                    $request->user->name,
                    $request->user->email,
                    $temporaryPassword,
                ),
                'is_read' => false,
            ]);

            return $temporaryPassword;
        });
    }
}

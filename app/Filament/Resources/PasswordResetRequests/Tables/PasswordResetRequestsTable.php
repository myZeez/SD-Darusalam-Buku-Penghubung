<?php

namespace App\Filament\Resources\PasswordResetRequests\Tables;

use App\Actions\Auth\ProcessPasswordResetRequest;
use App\Filament\Resources\PasswordResetRequests\PasswordResetRequestResource;
use App\Models\PasswordResetRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PasswordResetRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                TextColumn::make('user.name')
                    ->label('Akun')
                    ->description(fn (PasswordResetRequest $record): string => $record->user?->email ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.name')
                    ->label('Siswa Terkait')
                    ->description(fn (PasswordResetRequest $record): string => $record->student?->class?->name ?? 'Belum ada kelas')
                    ->placeholder('Belum terhubung'),
                TextColumn::make('teacher.name')
                    ->label('Wali Kelas')
                    ->placeholder('Belum tersedia'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'processed' => 'Selesai',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Diminta Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('processor.name')
                    ->label('Diproses Oleh')
                    ->placeholder('Belum diproses')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'processed' => 'Selesai',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('process')
                    ->label('Proses Reset')
                    ->icon('gmdi-lock-reset-o')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Buat Password Sementara')
                    ->modalDescription('Password akun akan diganti dan dikirim ke wali kelas melalui notifikasi.')
                    ->visible(fn (PasswordResetRequest $record): bool => $record->status === 'pending'
                        && PasswordResetRequestResource::canProcess())
                    ->action(function (PasswordResetRequest $record): void {
                        try {
                            app(ProcessPasswordResetRequest::class)->handle($record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Permintaan belum dapat diproses')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Password sementara sudah dikirim')
                            ->body('Wali kelas menerima email login dan password sementara melalui notifikasi.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('gmdi-cancel-o')
                    ->color('danger')
                    ->visible(fn (PasswordResetRequest $record): bool => $record->status === 'pending'
                        && PasswordResetRequestResource::canProcess())
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(fn (PasswordResetRequest $record, array $data) => $record->update([
                        'status' => 'rejected',
                        'admin_note' => $data['admin_note'],
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ])),
                ViewAction::make(),
            ])
            ->emptyStateHeading('Belum ada permintaan password')
            ->emptyStateDescription('Permintaan dari halaman login akan muncul di sini.');
    }
}

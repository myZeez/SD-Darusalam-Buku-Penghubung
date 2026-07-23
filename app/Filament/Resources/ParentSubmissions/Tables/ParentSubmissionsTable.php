<?php

namespace App\Filament\Resources\ParentSubmissions\Tables;

use App\Filament\Resources\ParentSubmissions\ParentSubmissionResource;
use App\Models\ParentSubmission;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParentSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'student.class',
                'parent.user',
                'reviewer',
            ]))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->description(fn (ParentSubmission $record): string => $record->student?->class?->name ?? 'Belum ada kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.user.name')
                    ->label('Orang Tua')
                    ->placeholder('Belum terhubung'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'family' => 'Keperluan Keluarga',
                        'late' => 'Pemberitahuan Terlambat',
                        'home_report' => 'Laporan Rumah',
                        'other' => 'Lainnya',
                        default => $state,
                    }),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('start_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn (ParentSubmission $record): ?string => $record->end_date?->ne($record->start_date)
                        ? 's.d. '.$record->end_date->format('d M Y')
                        : null)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Ditinjau Oleh')
                    ->placeholder('Belum ditinjau')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Laporan')
                    ->options([
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'family' => 'Keperluan Keluarga',
                        'late' => 'Pemberitahuan Terlambat',
                        'home_report' => 'Laporan Rumah',
                        'other' => 'Lainnya',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->groups([
                Group::make('student.class.name')
                    ->label('Kelas')
                    ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('student.class.name')
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('gmdi-check-circle-o')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ParentSubmission $record): bool => $record->status === 'pending'
                        && ParentSubmissionResource::canReview($record))
                    ->action(function (ParentSubmission $record): void {
                        abort_unless(ParentSubmissionResource::canReview($record), 403);
                        $record->update(['status' => 'approved']);
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('gmdi-cancel-o')
                    ->color('danger')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->visible(fn (ParentSubmission $record): bool => $record->status === 'pending'
                        && ParentSubmissionResource::canReview($record))
                    ->action(function (ParentSubmission $record, array $data): void {
                        abort_unless(ParentSubmissionResource::canReview($record), 403);
                        $record->update([
                            'status' => 'rejected',
                            'review_note' => $data['review_note'],
                        ]);
                    }),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (ParentSubmission $record): bool => ParentSubmissionResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ParentSubmissionResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengajuan orang tua')
            ->emptyStateDescription('Izin, sakit, dan laporan keluarga akan muncul di halaman ini.');
    }
}

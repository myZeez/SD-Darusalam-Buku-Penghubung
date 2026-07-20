<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query->with(['schoolClass', 'creator']);

                if (auth()->user()?->hasRole('orang_tua')) {
                    $query->with([
                        'responses' => fn ($query) => $query->where('user_id', auth()->id()),
                    ]);
                }

                return $query;
            })
            ->columns([
                ViewColumn::make('schedule_card')
                    ->label('Agenda')
                    ->view('filament.tables.columns.schedule-card'),
                TextColumn::make('title')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('description')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('location')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('activity_date')
                    ->date()
                    ->sortable()
                    ->hidden(),
                TextColumn::make('schoolClass.name')
                    ->searchable()
                    ->hidden(),
            ])
            ->defaultSort('activity_date')
            ->recordUrl(fn (Schedule $record): string => ScheduleResource::getUrl('view', ['record' => $record]))
            ->filters([])
            ->recordActions([
                self::parentResponseAction(),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Schedule $record): bool => ScheduleResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ScheduleResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada jadwal kegiatan')
            ->emptyStateDescription('Agenda kelas dan kegiatan sekolah akan tampil di sini.');
    }

    public static function parentResponseAction(): Action
    {
        return Action::make('parentResponse')
            ->label('Konfirmasi')
            ->icon('gmdi-how-to-reg-o')
            ->visible(fn (Schedule $record): bool => (auth()->user()?->hasRole('orang_tua') ?? false)
                && ScheduleResource::canView($record))
            ->modalHeading('Konfirmasi Kehadiran')
            ->modalDescription('Pilih respons untuk agenda ini. Anda dapat memperbaruinya kapan saja.')
            ->modalSubmitActionLabel('Simpan Konfirmasi')
            ->fillForm(function (Schedule $record): array {
                $response = $record->responses()->where('user_id', auth()->id())->first();

                return [
                    'response' => $response?->response ?? 'attending',
                    'proposed_date' => $response?->proposed_date,
                    'note' => $response?->note,
                ];
            })
            ->form([
                ToggleButtons::make('response')
                    ->label('Respons Kehadiran')
                    ->options([
                        'attending' => 'Saya Akan Datang',
                        'unavailable' => 'Belum Bisa Datang',
                        'reschedule' => 'Jadwalkan Ulang',
                    ])
                    ->icons([
                        'attending' => 'gmdi-event-available-o',
                        'unavailable' => 'gmdi-event-busy-o',
                        'reschedule' => 'gmdi-update-o',
                    ])
                    ->colors([
                        'attending' => 'success',
                        'unavailable' => 'danger',
                        'reschedule' => 'warning',
                    ])
                    ->columns(['default' => 1, 'sm' => 3])
                    ->live()
                    ->required(),
                DatePicker::make('proposed_date')
                    ->label('Usulan Tanggal Baru')
                    ->minDate(today())
                    ->visible(fn (Get $get): bool => $get('response') === 'reschedule')
                    ->required(fn (Get $get): bool => $get('response') === 'reschedule'),
                Textarea::make('note')
                    ->label('Catatan untuk Sekolah')
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (Schedule $record, array $data): void {
                ScheduleResource::saveParentResponse($record, $data);

                Notification::make()
                    ->title('Konfirmasi kehadiran tersimpan')
                    ->success()
                    ->send();
            });
    }
}

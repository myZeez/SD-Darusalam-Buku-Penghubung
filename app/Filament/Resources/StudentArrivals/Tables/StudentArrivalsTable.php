<?php

namespace App\Filament\Resources\StudentArrivals\Tables;

use App\Exports\StudentArrivalsExport;
use App\Filament\Resources\StudentArrivals\StudentArrivalResource;
use App\Models\SchoolClass;
use App\Models\StudentArrival;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class StudentArrivalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'student',
                'schoolClass',
                'recorder',
            ]))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->description(fn (StudentArrival $record): string => $record->student?->nis ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->badge()
                    ->placeholder('Belum ada kelas'),
                TextColumn::make('arrival_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('arrival_time')
                    ->label('Waktu Datang')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'late' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'late' ? 'Terlambat' : 'Tepat Waktu'),
                TextColumn::make('recorder.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('Sistem')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Kedatangan')
                    ->options([
                        'on_time' => 'Tepat Waktu',
                        'late' => 'Terlambat',
                    ]),
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('arrival_date')
                    ->label('Tanggal')
                    ->form([
                        DatePicker::make('date')->label('Tanggal Kedatangan'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('arrival_date', $date))),
            ])
            ->groups([
                Group::make('schoolClass.name')
                    ->label('Kelas')
                    ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('schoolClass.name')
            ->defaultSort('arrival_date', 'desc')
            ->headerActions([
                Action::make('export')
                    ->label('Unduh Excel')
                    ->icon('gmdi-download-o')
                    ->visible(fn (): bool => auth()->user()?->can('export reports') ?? false)
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(today()),
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->placeholder('Semua kelas'),
                    ])
                    ->action(fn (array $data) => Excel::download(
                        new StudentArrivalsExport($data),
                        'rekap-kedatangan-'.now()->format('Ymd-His').'.xlsx',
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (StudentArrival $record): bool => StudentArrivalResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => StudentArrivalResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada siswa yang check-in')
            ->emptyStateDescription('Pencatatan dari loket akan muncul di sini secara otomatis.');
    }
}

<?php

namespace App\Filament\Resources\HomeActivities\Tables;

use App\Filament\Resources\HomeActivities\HomeActivityResource;
use App\Models\HomeActivity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomeActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->stackedOnMobile(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.parent.user.name')
                    ->label('Orang Tua')
                    ->hidden(fn (): bool => auth()->user()?->hasAnyRole(['orang_tua', 'siswa']) ?? false)
                    ->searchable(),
                TextColumn::make('activity_date')
                    ->label('Tanggal Aktivitas')
                    ->date()
                    ->sortable(),
                TextColumn::make('activity_category_summary')
                    ->label('Kategori Aktivitas')
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->options(function (): array {
                        $user = auth()->user();
                        $students = $user?->hasRole('guru')
                            ? $user->managedStudents()
                            : $user?->accessibleStudents();

                        return $students?->orderBy('name')->pluck('name', 'id')->all() ?? [];
                    })
                    ->searchable()
                    ->preload(),
                Filter::make('class_id')
                    ->label('Kelas')
                    ->visible(fn (): bool => auth()->user()?->hasRole('guru') ?? false)
                    ->form([
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => auth()->user()?->managedStudents()
                                ->whereNotNull('class_id')
                                ->with('class:id,name')
                                ->get()
                                ->pluck('class.name', 'class_id')
                                ->all() ?? [])
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['class_id'] ?? null, fn (Builder $query, int $classId): Builder => $query
                            ->whereHas('student', fn (Builder $studentQuery): Builder => $studentQuery->where('class_id', $classId)))),
                Filter::make('activity_date')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('activity_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('activity_date', '<=', $date))),
            ])
            ->defaultSort('activity_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (HomeActivity $record): bool => HomeActivityResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => HomeActivityResource::canDeleteAny()),
                ]),
            ])
            ->emptyStateHeading('Belum ada laporan rumah')
            ->emptyStateDescription('Aktivitas yang dicatat orang tua akan muncul di sini.');
    }
}

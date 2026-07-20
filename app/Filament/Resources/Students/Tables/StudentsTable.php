<?php

namespace App\Filament\Resources\Students\Tables;

use App\Exports\StudentsExport;
use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentsImport;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        if (auth()->user()?->hasAnyRole(['orang_tua', 'siswa'])) {
            return self::configureFamilyTable($table);
        }

        return $table
            ->columns([
                TextColumn::make('class.name')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('parent.user.name')
                    ->label('Orang Tua')
                    ->description(fn ($record): ?string => $record->parent?->user?->email)
                    ->placeholder('Belum dihubungkan')
                    ->searchable(),
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->description(fn ($record): ?string => $record->user?->email)
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        default => $state,
                    })
                    ->searchable(),
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
                //
            ])
            ->headerActions([
                Action::make('import')
                    ->label('Impor Excel')
                    ->visible(fn (): bool => StudentResource::canCreate())
                    ->form([
                        FileUpload::make('file')
                            ->label('Berkas Excel')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->required(),
                    ])
                    ->action(fn (array $data) => Excel::import(new StudentsImport(auth()->user()), Storage::disk('local')->path($data['file']))),
                Action::make('export')
                    ->label('Ekspor Excel')
                    ->visible(fn (): bool => StudentResource::canCreate())
                    ->action(fn () => Excel::download(new StudentsExport, 'data-siswa.xlsx')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Student $record): bool => StudentResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => StudentResource::canDeleteAny()),
                ]),
            ]);
    }

    private static function configureFamilyTable(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'class.teacher.user',
                'user',
            ]))
            ->columns([
                ViewColumn::make('ringkasan_anak')
                    ->label('Anak')
                    ->view('filament.tables.columns.student-family-card'),
                TextColumn::make('name')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('nis')
                    ->searchable()
                    ->hidden(),
                TextColumn::make('class.name')
                    ->searchable()
                    ->hidden(),
            ])
            ->recordUrl(null)
            ->filters([])
            ->recordActions([])
            ->headerActions([])
            ->toolbarActions([]);
    }
}

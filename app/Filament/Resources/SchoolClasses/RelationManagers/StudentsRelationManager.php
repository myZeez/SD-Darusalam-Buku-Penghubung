<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'Siswa di Kelas Ini';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->description(fn (Student $record): ?string => $record->user?->email)
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        default => 'Belum diisi',
                    }),
                TextColumn::make('parent.user.name')
                    ->label('Orang Tua')
                    ->placeholder('Belum dihubungkan'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('viewStudent')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Student $record): string => StudentResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}

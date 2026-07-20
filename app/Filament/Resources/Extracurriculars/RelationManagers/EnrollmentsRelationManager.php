<?php

namespace App\Filament\Resources\Extracurriculars\RelationManagers;

use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Models\ExtracurricularEnrollment;
use App\Models\Student;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Peserta Ekstrakurikuler';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student.name')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->description(fn (ExtracurricularEnrollment $record): string => $record->student?->nis ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.class.name')
                    ->label('Kelas')
                    ->badge()
                    ->placeholder('Belum ada kelas'),
                TextColumn::make('joined_at')
                    ->label('Tanggal Bergabung')
                    ->date('d M Y')
                    ->placeholder('Belum dicatat'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Peserta')
                    ->visible(fn (): bool => $this->canManage())
                    ->form($this->formSchema()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canManage())
                    ->form($this->formSchema()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canManage()),
            ])
            ->emptyStateHeading('Belum ada peserta')
            ->emptyStateDescription('Siswa yang ditambahkan akan otomatis masuk daftar absensi sesi berikutnya.');
    }

    /** @return array<int, mixed> */
    private function formSchema(): array
    {
        return [
            Select::make('student_id')
                ->label('Siswa')
                ->relationship(
                    name: 'student',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->where('status', 'active')
                        ->with('class')
                        ->orderBy('name'),
                )
                ->getOptionLabelFromRecordUsing(fn (Student $student): string => sprintf(
                    '%s - %s (%s)',
                    $student->nis,
                    $student->name,
                    $student->class?->name ?? 'belum ada kelas',
                ))
                ->searchable(['nis', 'name'])
                ->preload()
                ->required(),
            DatePicker::make('joined_at')
                ->label('Tanggal Bergabung')
                ->default(today()),
            Select::make('status')
                ->label('Status')
                ->options(['active' => 'Aktif', 'inactive' => 'Tidak Aktif'])
                ->default('active')
                ->required(),
        ];
    }

    private function canManage(): bool
    {
        return ExtracurricularResource::canEdit($this->getOwnerRecord());
    }
}

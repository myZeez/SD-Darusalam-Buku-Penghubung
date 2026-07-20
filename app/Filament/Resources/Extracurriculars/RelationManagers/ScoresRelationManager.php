<?php

namespace App\Filament\Resources\Extracurriculars\RelationManagers;

use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use App\Models\ExtracurricularScore;
use App\Models\Student;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScoresRelationManager extends RelationManager
{
    protected static string $relationship = 'scores';

    protected static ?string $title = 'Materi dan Nilai';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->description(fn (ExtracurricularScore $record): string => $record->student?->class?->name ?? 'Belum ada kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Materi/Penilaian')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 0)
                    ->badge()
                    ->color(fn (float|string $state): string => (float) $state >= 75 ? 'success' : 'warning'),
                TextColumn::make('assessed_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(70)
                    ->wrap()
                    ->placeholder('Belum ada catatan'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Nilai')
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
            ->defaultSort('assessed_at', 'desc')
            ->emptyStateHeading('Belum ada nilai')
            ->emptyStateDescription('Nilai dapat dicatat per materi untuk setiap peserta ekstrakurikuler.');
    }

    /** @return array<int, mixed> */
    private function formSchema(): array
    {
        $extracurricularId = $this->getOwnerRecord()->id;

        return [
            Select::make('student_id')
                ->label('Siswa')
                ->relationship(
                    name: 'student',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->whereHas('extracurricularEnrollments', fn (Builder $query): Builder => $query
                            ->where('extracurricular_id', $extracurricularId)
                            ->where('status', 'active'))
                        ->with('class')
                        ->orderBy('name'),
                )
                ->getOptionLabelFromRecordUsing(fn (Student $student): string => sprintf(
                    '%s (%s)',
                    $student->name,
                    $student->class?->name ?? 'belum ada kelas',
                ))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('title')
                ->label('Materi/Penilaian')
                ->required(),
            TextInput::make('score')
                ->label('Nilai')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->required(),
            DatePicker::make('assessed_at')
                ->label('Tanggal Penilaian')
                ->default(today())
                ->required(),
            Textarea::make('notes')
                ->label('Catatan Perkembangan')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    private function canManage(): bool
    {
        return ExtracurricularResource::canEdit($this->getOwnerRecord());
    }
}

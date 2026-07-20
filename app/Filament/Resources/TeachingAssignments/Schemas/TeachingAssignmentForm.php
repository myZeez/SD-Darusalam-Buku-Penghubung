<?php

namespace App\Filament\Resources\TeachingAssignments\Schemas;

use App\Models\AcademicPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class TeachingAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Penugasan Mengajar')
                ->description('Hubungkan satu Guru dengan satu atau banyak mata pelajaran dalam kelas dan periode akademik tertentu.')
                ->icon(Heroicon::OutlinedAcademicCap)
                ->columns(['md' => 2])
                ->columnSpanFull()
                ->schema([
                    Select::make('academic_period_id')
                        ->relationship('academicPeriod', 'academic_year')
                        ->getOptionLabelFromRecordUsing(fn (AcademicPeriod $record): string => $record->label)
                        ->default(fn (): ?int => AcademicPeriod::query()->where('is_active', true)->value('id'))
                        ->afterStateUpdated(function (Set $set): void {
                            $set('class_id', null);
                        })
                        ->live()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Periode Akademik'),
                    Select::make('teacher_id')
                        ->relationship(
                            name: 'teacher',
                            titleAttribute: 'nip',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->with('user')->orderBy('nip'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Teacher $record): string => sprintf(
                            '%s - %s',
                            $record->user?->name ?? 'Guru',
                            $record->nip ?: 'NIP belum tersedia',
                        ))
                        ->searchable(['nip'])
                        ->preload()
                        ->required()
                        ->label('Guru Pengajar'),
                    Select::make('class_id')
                        ->relationship(
                            name: 'schoolClass',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                ->when($get('academic_period_id'), fn (Builder $query, $periodId): Builder => $query->where('academic_period_id', $periodId))
                                ->orderBy('grade_level')
                                ->orderBy('name'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Kelas'),
                    Select::make('subject_id')
                        ->relationship(
                            name: 'subject',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'),
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} - {$record->name}")
                        ->searchable(['code', 'name'])
                        ->preload()
                        ->required()
                        ->label('Mata Pelajaran')
                        ->visibleOn('edit')
                        ->dehydratedWhenHidden(false),
                    CheckboxList::make('subject_ids')
                        ->label('Mata Pelajaran yang Diampu')
                        ->helperText('Centang satu atau beberapa mata pelajaran. Gunakan "Pilih semua" jika Guru mengampu seluruh mata pelajaran.')
                        ->options(fn (): array => Subject::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Subject $subject): array => [
                                $subject->id => "{$subject->code} - {$subject->name}",
                            ])
                            ->all())
                        ->searchable()
                        ->bulkToggleable()
                        ->selectAllAction(fn ($action) => $action->label('Pilih semua'))
                        ->deselectAllAction(fn ($action) => $action->label('Hapus semua pilihan'))
                        ->columns(['sm' => 2])
                        ->required()
                        ->minItems(1)
                        ->visibleOn('create')
                        ->dehydratedWhenHidden(false)
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label('Penugasan Aktif')
                        ->default(true)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Catatan Penugasan')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

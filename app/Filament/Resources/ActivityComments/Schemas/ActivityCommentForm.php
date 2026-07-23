<?php

namespace App\Filament\Resources\ActivityComments\Schemas;

use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ActivityCommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konteks Diskusi')
                    ->description('Pilih laporan terkait agar pembahasan mudah ditelusuri kembali.')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columnSpanFull()
                    ->schema([
                        MorphToSelect::make('activity')
                            ->label('Aktivitas yang Dikomentari')
                            ->types([
                                MorphToSelect\Type::make(SchoolActivity::class)
                                    ->label('Laporan Sekolah')
                                    ->titleAttribute('activity_date')
                                    ->modifyOptionsQueryUsing(fn (Builder $query): Builder => self::accessibleActivityQuery($query))
                                    ->getOptionLabelFromRecordUsing(fn (SchoolActivity $record): string => self::schoolActivityLabel($record)),
                                MorphToSelect\Type::make(HomeActivity::class)
                                    ->label('Laporan Rumah')
                                    ->titleAttribute('activity_date')
                                    ->modifyOptionsQueryUsing(fn (Builder $query): Builder => self::accessibleActivityQuery($query, true))
                                    ->getOptionLabelFromRecordUsing(fn (HomeActivity $record): string => self::homeActivityLabel($record)),
                            ])
                            ->typeSelectToggleButtons()
                            ->modifyTypeSelectUsing(fn (ToggleButtons $toggleButtons): ToggleButtons => $toggleButtons->grouped())
                            ->searchable()
                            ->preload()
                            ->optionsLimit(100)
                            ->required(),
                    ]),
                Section::make('Topik dan Pembuka Diskusi')
                    ->description('Satu topik untuk satu pembahasan. Diskusi yang sudah selesai dapat ditutup.')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->columnSpanFull()
                    ->schema([
                        ToggleButtons::make('category')
                            ->label('Kategori')
                            ->options([
                                'learning' => 'Pelajaran',
                                'behaviour' => 'Sikap & Perkembangan',
                                'attendance' => 'Kehadiran',
                                'other' => 'Lainnya',
                            ])
                            ->default('other')
                            ->inline()
                            ->grouped()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('topic')
                            ->label('Topik yang dibahas')
                            ->placeholder('Contoh: Pendampingan membaca di rumah')
                            ->maxLength(150)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('comment')
                            ->label('Penjelasan awal')
                            ->placeholder('Jelaskan hal yang ingin didiskusikan secara singkat dan jelas.')
                            ->rows(6)
                            ->required(),
                    ]),
            ]);
    }

    private static function accessibleActivityQuery(Builder $query, bool $homeActivity = false): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $students = $homeActivity && $user->hasRole('guru')
            ? $user->managedStudents()
            : $user->accessibleStudents();

        return $query
            ->with('student')
            ->whereIn('student_id', $students->select('students.id'))
            ->latest('activity_date');
    }

    private static function schoolActivityLabel(SchoolActivity $record): string
    {
        $attendance = match ($record->attendance) {
            'present' => 'Hadir',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'absent' => 'Alpa',
            default => 'Kehadiran belum diisi',
        };

        return sprintf(
            '%s - %s - %s',
            $record->student?->name ?? 'Siswa tidak ditemukan',
            $record->activity_date?->format('d M Y') ?? 'Tanpa tanggal',
            $attendance,
        );
    }

    private static function homeActivityLabel(HomeActivity $record): string
    {
        return sprintf(
            '%s - %s',
            $record->student?->name ?? 'Siswa tidak ditemukan',
            $record->activity_date?->format('d M Y') ?? 'Tanpa tanggal',
        );
    }
}

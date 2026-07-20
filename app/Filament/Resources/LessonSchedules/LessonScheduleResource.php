<?php

namespace App\Filament\Resources\LessonSchedules;

use App\Filament\Resources\LessonSchedules\Pages\CreateLessonSchedule;
use App\Filament\Resources\LessonSchedules\Pages\EditLessonSchedule;
use App\Filament\Resources\LessonSchedules\Pages\ListLessonSchedules;
use App\Filament\Resources\LessonSchedules\Pages\ViewLessonSchedule;
use App\Filament\Resources\LessonSchedules\Schemas\LessonScheduleForm;
use App\Filament\Resources\LessonSchedules\Schemas\LessonScheduleInfolist;
use App\Filament\Resources\LessonSchedules\Tables\LessonSchedulesTable;
use App\Models\LessonSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LessonScheduleResource extends Resource
{
    protected static ?string $model = LessonSchedule::class;

    protected static ?string $modelLabel = 'Jadwal Pelajaran';

    protected static ?string $pluralModelLabel = 'Jadwal Pelajaran';

    protected static ?string $navigationLabel = 'Jadwal Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-calendar-view-week-o';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Jadwal Mengajar' : 'Jadwal Pelajaran';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user?->hasRole('guru')) {
            return $query
                ->where('is_active', true)
                ->whereHas('teachingAssignment', function (Builder $query) use ($user): void {
                    $query
                        ->whereHas('teacher', fn (Builder $query): Builder => $query->where('user_id', $user->id))
                        ->orWhereIn('class_id', $user->managedClasses()->select('classes.id'));
                });
        }

        if ($user?->hasRole('orang_tua')) {
            return $query
                ->where('is_active', true)
                ->whereHas('teachingAssignment', fn (Builder $query): Builder => $query
                    ->whereIn('class_id', $user->accessibleClasses()->select('classes.id')));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view lesson schedules') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasAnyRole(['guru', 'orang_tua']) ?? false));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('manage lesson schedules') ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDeleteAny(): bool
    {
        return static::canCreate();
    }

    public static function form(Schema $schema): Schema
    {
        return LessonScheduleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LessonScheduleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonSchedules::route('/'),
            'create' => CreateLessonSchedule::route('/create'),
            'view' => ViewLessonSchedule::route('/{record}'),
            'edit' => EditLessonSchedule::route('/{record}/edit'),
        ];
    }
}

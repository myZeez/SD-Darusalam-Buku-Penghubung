<?php

namespace App\Filament\Resources\LessonPeriods;

use App\Filament\Resources\LessonPeriods\Pages\CreateLessonPeriod;
use App\Filament\Resources\LessonPeriods\Pages\EditLessonPeriod;
use App\Filament\Resources\LessonPeriods\Pages\ListLessonPeriods;
use App\Filament\Resources\LessonPeriods\Pages\ViewLessonPeriod;
use App\Filament\Resources\LessonPeriods\Schemas\LessonPeriodForm;
use App\Filament\Resources\LessonPeriods\Schemas\LessonPeriodInfolist;
use App\Filament\Resources\LessonPeriods\Tables\LessonPeriodsTable;
use App\Models\LessonPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LessonPeriodResource extends Resource
{
    protected static ?string $model = LessonPeriod::class;

    protected static ?string $modelLabel = 'Periode Pelajaran';

    protected static ?string $pluralModelLabel = 'Periode Pelajaran';

    protected static ?string $navigationLabel = 'Periode Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-schedule-o';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('view lesson periods') ?? false);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('manage lesson periods') ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate() && ! $record->lessonSchedules()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return LessonPeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LessonPeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonPeriods::route('/'),
            'create' => CreateLessonPeriod::route('/create'),
            'view' => ViewLessonPeriod::route('/{record}'),
            'edit' => EditLessonPeriod::route('/{record}/edit'),
        ];
    }
}

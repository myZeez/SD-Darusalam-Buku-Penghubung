<?php

namespace App\Filament\Resources\AcademicPeriods;

use App\Filament\Resources\AcademicPeriods\Pages\CreateAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Pages\EditAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Pages\ListAcademicPeriods;
use App\Filament\Resources\AcademicPeriods\Pages\ViewAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Schemas\AcademicPeriodForm;
use App\Filament\Resources\AcademicPeriods\Schemas\AcademicPeriodInfolist;
use App\Filament\Resources\AcademicPeriods\Tables\AcademicPeriodsTable;
use App\Models\AcademicPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AcademicPeriodResource extends Resource
{
    protected static ?string $model = AcademicPeriod::class;

    protected static ?string $modelLabel = 'Periode Akademik';

    protected static ?string $pluralModelLabel = 'Tahun Ajaran dan Semester';

    protected static ?string $navigationLabel = 'Tahun Ajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-date-range-o';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('view academic periods') ?? false);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('manage academic periods') ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate()
            && ! $record->is_active
            && ! $record->classes()->exists()
            && ! $record->teachingAssignments()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AcademicPeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AcademicPeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcademicPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicPeriods::route('/'),
            'create' => CreateAcademicPeriod::route('/create'),
            'view' => ViewAcademicPeriod::route('/{record}'),
            'edit' => EditAcademicPeriod::route('/{record}/edit'),
        ];
    }
}

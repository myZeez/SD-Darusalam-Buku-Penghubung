<?php

namespace App\Filament\Resources\SchoolActivities;

use App\Filament\Resources\SchoolActivities\Pages\CreateSchoolActivity;
use App\Filament\Resources\SchoolActivities\Pages\EditSchoolActivity;
use App\Filament\Resources\SchoolActivities\Pages\ListSchoolActivities;
use App\Filament\Resources\SchoolActivities\Pages\ViewSchoolActivity;
use App\Filament\Resources\SchoolActivities\Schemas\SchoolActivityForm;
use App\Filament\Resources\SchoolActivities\Schemas\SchoolActivityInfolist;
use App\Filament\Resources\SchoolActivities\Tables\SchoolActivitiesTable;
use App\Models\SchoolActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolActivityResource extends Resource
{
    protected static ?string $model = SchoolActivity::class;

    protected static ?string $modelLabel = 'Laporan Harian Sekolah';

    protected static ?string $pluralModelLabel = 'Laporan Harian Sekolah';

    protected static ?string $navigationLabel = 'Laporan Sekolah';

    protected static string|\UnitEnum|null $navigationGroup = 'Buku Penghubung';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-assignment-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('student_id', $user->accessibleStudents()->select('students.id'));
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view school activities') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasAnyRole(['guru', 'orang_tua']) ?? false));
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return static::canViewAny() && $user->canAccessStudent($record->student_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('orang_tua') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return SchoolActivityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchoolActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolActivities::route('/'),
            'create' => CreateSchoolActivity::route('/create'),
            'view' => ViewSchoolActivity::route('/{record}'),
            'edit' => EditSchoolActivity::route('/{record}/edit'),
        ];
    }
}

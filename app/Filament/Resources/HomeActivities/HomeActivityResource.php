<?php

namespace App\Filament\Resources\HomeActivities;

use App\Filament\Resources\HomeActivities\Pages\CreateHomeActivity;
use App\Filament\Resources\HomeActivities\Pages\EditHomeActivity;
use App\Filament\Resources\HomeActivities\Pages\ListHomeActivities;
use App\Filament\Resources\HomeActivities\Pages\ViewHomeActivity;
use App\Filament\Resources\HomeActivities\Schemas\HomeActivityForm;
use App\Filament\Resources\HomeActivities\Schemas\HomeActivityInfolist;
use App\Filament\Resources\HomeActivities\Tables\HomeActivitiesTable;
use App\Models\HomeActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeActivityResource extends Resource
{
    protected static ?string $model = HomeActivity::class;

    protected static ?string $modelLabel = 'Aktivitas Rumah';

    protected static ?string $pluralModelLabel = 'Aktivitas Rumah';

    protected static ?string $navigationLabel = 'Aktivitas Rumah';

    protected static string|\UnitEnum|null $navigationGroup = 'Buku Penghubung';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-home-work-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $students = $user->hasRole('guru') ? $user->managedStudents() : $user->accessibleStudents();

        return $query->whereIn('student_id', $students->select('students.id'));
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view home activities') ?? false)
            && ! ($user?->hasRole('siswa') ?? false);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (! ($user?->can('view home activities') ?? false)) {
            return false;
        }

        return $user->hasRole('guru')
            ? $user->managedStudents()->whereKey($record->student_id)->exists()
            : $user->canAccessStudent($record->student_id);
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
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return HomeActivityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HomeActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeActivitiesTable::configure($table);
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
            'index' => ListHomeActivities::route('/'),
            'create' => CreateHomeActivity::route('/create'),
            'view' => ViewHomeActivity::route('/{record}'),
            'edit' => EditHomeActivity::route('/{record}/edit'),
        ];
    }
}

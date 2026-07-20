<?php

namespace App\Filament\Resources\SchoolClasses;

use App\Filament\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClasses\Pages\ListSchoolClasses;
use App\Filament\Resources\SchoolClasses\Pages\ViewSchoolClass;
use App\Filament\Resources\SchoolClasses\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\SchoolClasses\Schemas\SchoolClassForm;
use App\Filament\Resources\SchoolClasses\Schemas\SchoolClassInfolist;
use App\Filament\Resources\SchoolClasses\Tables\SchoolClassesTable;
use App\Models\SchoolClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static ?string $modelLabel = 'Kelas';

    protected static ?string $pluralModelLabel = 'Kelas';

    protected static ?string $navigationLabel = 'Data Kelas';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sekolah';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-class-o';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Kelas dan Siswa' : 'Data Kelas';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->whereIn('classes.id', $user?->accessibleClasses()->select('classes.id') ?? []);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view classes') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('view classes') ?? false)
            && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage classes') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage classes') ?? false)
            || (($user?->hasRole('guru') ?? false)
                && ($user?->can('view classes') ?? false)
                && $user->managedClasses()->whereKey($record)->exists());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage classes') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('manage classes') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return SchoolClassForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchoolClassInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'view' => ViewSchoolClass::route('/{record}'),
            'edit' => EditSchoolClass::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Resources\Teachers\Pages\ViewTeacher;
use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\Schemas\TeacherInfolist;
use App\Filament\Resources\Teachers\Tables\TeachersTable;
use App\Models\Teacher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $modelLabel = 'Guru';

    protected static ?string $pluralModelLabel = 'Guru';

    protected static ?string $navigationLabel = 'Data Guru';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sekolah';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-school-o';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Profil Guru' : 'Data Guru';
    }

    public static function getNavigationUrl(): string
    {
        $user = auth()->user();

        if ($user?->hasRole('guru') && $user->teacher) {
            return static::getUrl('view', ['record' => $user->teacher]);
        }

        return parent::getNavigationUrl();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user?->id ?? 0);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view teachers') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('view teachers') ?? false)
            && (($user?->isAdmin() ?? false) || $record->user_id === $user?->id);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage teachers') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage teachers') ?? false)
            || (($user?->can('view teachers') ?? false) && $record->user_id === $user?->id);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage teachers') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('manage teachers') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return TeacherForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TeacherInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachersTable::configure($table);
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
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'view' => ViewTeacher::route('/{record}'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}

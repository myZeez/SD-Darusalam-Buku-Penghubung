<?php

namespace App\Filament\Resources\Extracurriculars;

use App\Filament\Resources\Extracurriculars\Pages\CreateExtracurricular;
use App\Filament\Resources\Extracurriculars\Pages\EditExtracurricular;
use App\Filament\Resources\Extracurriculars\Pages\ListExtracurriculars;
use App\Filament\Resources\Extracurriculars\Pages\ViewExtracurricular;
use App\Filament\Resources\Extracurriculars\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Extracurriculars\RelationManagers\ScoresRelationManager;
use App\Filament\Resources\Extracurriculars\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\Extracurriculars\Schemas\ExtracurricularForm;
use App\Filament\Resources\Extracurriculars\Schemas\ExtracurricularInfolist;
use App\Filament\Resources\Extracurriculars\Tables\ExtracurricularsTable;
use App\Models\Extracurricular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularResource extends Resource
{
    protected static ?string $model = Extracurricular::class;

    protected static ?string $modelLabel = 'Ekstrakurikuler';

    protected static ?string $pluralModelLabel = 'Ekstrakurikuler';

    protected static ?string $navigationLabel = 'Ekstrakurikuler';

    protected static string|\UnitEnum|null $navigationGroup = 'Ekstrakurikuler';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-sports-soccer-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user?->hasAnyRole(['orang_tua', 'siswa'])) {
            return $query->whereHas(
                'enrollments',
                fn (Builder $query): Builder => $query
                    ->where('status', 'active')
                    ->whereIn('student_id', $user->accessibleStudents()->select('students.id')),
            );
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view extracurriculars') ?? false)
            && ! ($user?->hasRole('guru') ?? false);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage extracurriculars') ?? false)
            && ($user?->isAdmin() ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage extracurriculars') ?? false)
            && ($user?->isAdmin() ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ExtracurricularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExtracurricularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtracurricularsTable::configure($table);
    }

    public static function getRelations(): array
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            return [];
        }

        return [
            EnrollmentsRelationManager::class,
            SessionsRelationManager::class,
            ScoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExtracurriculars::route('/'),
            'create' => CreateExtracurricular::route('/create'),
            'view' => ViewExtracurricular::route('/{record}'),
            'edit' => EditExtracurricular::route('/{record}/edit'),
        ];
    }
}

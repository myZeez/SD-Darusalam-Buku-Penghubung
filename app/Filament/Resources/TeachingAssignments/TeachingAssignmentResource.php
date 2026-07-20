<?php

namespace App\Filament\Resources\TeachingAssignments;

use App\Filament\Resources\TeachingAssignments\Pages\CreateTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Pages\EditTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Pages\ListTeachingAssignments;
use App\Filament\Resources\TeachingAssignments\Pages\ViewTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Schemas\TeachingAssignmentForm;
use App\Filament\Resources\TeachingAssignments\Schemas\TeachingAssignmentInfolist;
use App\Filament\Resources\TeachingAssignments\Tables\TeachingAssignmentsTable;
use App\Models\TeachingAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeachingAssignmentResource extends Resource
{
    protected static ?string $model = TeachingAssignment::class;

    protected static ?string $modelLabel = 'Penugasan Guru';

    protected static ?string $pluralModelLabel = 'Penugasan Guru';

    protected static ?string $navigationLabel = 'Penugasan Guru';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-assignment-ind-o';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('guru') ? 'Mata Pelajaran Kelas' : 'Penugasan Guru';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user?->hasRole('guru')) {
            return $query->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('teacher', fn (Builder $query): Builder => $query->where('user_id', $user->id))
                    ->orWhereIn('class_id', $user->managedClasses()->select('classes.id'));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view teaching assignments') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasRole('guru') ?? false));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('manage teaching assignments') ?? false);
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
        return TeachingAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TeachingAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachingAssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachingAssignments::route('/'),
            'create' => CreateTeachingAssignment::route('/create'),
            'view' => ViewTeachingAssignment::route('/{record}'),
            'edit' => EditTeachingAssignment::route('/{record}/edit'),
        ];
    }
}

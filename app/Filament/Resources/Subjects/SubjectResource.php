<?php

namespace App\Filament\Resources\Subjects;

use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Resources\Subjects\Pages\ViewSubject;
use App\Filament\Resources\Subjects\Schemas\SubjectForm;
use App\Filament\Resources\Subjects\Schemas\SubjectInfolist;
use App\Filament\Resources\Subjects\Tables\SubjectsTable;
use App\Models\Subject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $modelLabel = 'Mata Pelajaran';

    protected static ?string $pluralModelLabel = 'Mata Pelajaran';

    protected static ?string $navigationLabel = 'Mata Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-menu-book-o';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('view subjects') ?? false);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) && ($user?->can('manage subjects') ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate() && ! $record->teachingAssignments()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SubjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'view' => ViewSubject::route('/{record}'),
            'edit' => EditSubject::route('/{record}/edit'),
        ];
    }
}

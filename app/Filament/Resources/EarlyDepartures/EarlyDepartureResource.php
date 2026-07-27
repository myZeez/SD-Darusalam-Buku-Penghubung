<?php

namespace App\Filament\Resources\EarlyDepartures;

use App\Filament\Resources\EarlyDepartures\Pages\CreateEarlyDeparture;
use App\Filament\Resources\EarlyDepartures\Pages\EditEarlyDeparture;
use App\Filament\Resources\EarlyDepartures\Pages\ListEarlyDepartures;
use App\Filament\Resources\EarlyDepartures\Pages\ViewEarlyDeparture;
use App\Filament\Resources\EarlyDepartures\Schemas\EarlyDepartureForm;
use App\Filament\Resources\EarlyDepartures\Tables\EarlyDeparturesTable;
use App\Models\EarlyDeparture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EarlyDepartureResource extends Resource
{
    protected static ?string $model = EarlyDeparture::class;

    protected static ?string $modelLabel = 'Pulang Cepat';

    protected static ?string $pluralModelLabel = 'Pulang Cepat';

    protected static ?string $navigationLabel = 'Pulang Cepat';

    protected static string|\UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-directions-walk-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->whereIn('student_id', $user?->managedStudents()->select('students.id') ?? []);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view attendances') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasRole('guru') ?? false));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage attendances') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasRole('guru') ?? false));
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return EarlyDepartureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EarlyDeparturesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEarlyDepartures::route('/'),
            'create' => CreateEarlyDeparture::route('/create'),
            'view' => ViewEarlyDeparture::route('/{record}'),
            'edit' => EditEarlyDeparture::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\StudentArrivals;

use App\Filament\Resources\StudentArrivals\Pages\CreateStudentArrival;
use App\Filament\Resources\StudentArrivals\Pages\EditStudentArrival;
use App\Filament\Resources\StudentArrivals\Pages\ListStudentArrivals;
use App\Filament\Resources\StudentArrivals\Pages\LoketArrival;
use App\Filament\Resources\StudentArrivals\Pages\ViewStudentArrival;
use App\Filament\Resources\StudentArrivals\Schemas\StudentArrivalForm;
use App\Filament\Resources\StudentArrivals\Schemas\StudentArrivalInfolist;
use App\Filament\Resources\StudentArrivals\Tables\StudentArrivalsTable;
use App\Models\StudentArrival;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentArrivalResource extends Resource
{
    protected static ?string $model = StudentArrival::class;

    protected static ?string $modelLabel = 'Kedatangan Siswa';

    protected static ?string $pluralModelLabel = 'Kedatangan Siswa';

    protected static ?string $navigationLabel = 'Kedatangan Siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-login-o';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('loket')
            ? 'Catat Kedatangan'
            : parent::getNavigationLabel();
    }

    public static function getNavigationUrl(): string
    {
        return auth()->user()?->hasRole('loket')
            ? static::getUrl('desk')
            : parent::getNavigationUrl();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user?->hasRole('loket')) {
            return $query->whereDate('arrival_date', today());
        }

        return $query->whereIn('student_id', $user?->accessibleStudents()->select('students.id') ?? []);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view arrivals') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasRole('loket') ?? false));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage arrivals') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage arrivals') ?? false)
            && ($user->isAdmin() || ($record->recorded_by === $user->id && $record->arrival_date?->isToday()));
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
        return StudentArrivalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StudentArrivalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentArrivalsTable::configure($table);
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
            'index' => ListStudentArrivals::route('/'),
            'desk' => LoketArrival::route('/loket'),
            'create' => CreateStudentArrival::route('/create'),
            'view' => ViewStudentArrival::route('/{record}'),
            'edit' => EditStudentArrival::route('/{record}/edit'),
        ];
    }
}

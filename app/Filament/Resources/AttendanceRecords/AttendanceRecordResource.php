<?php

namespace App\Filament\Resources\AttendanceRecords;

use App\Filament\Resources\AttendanceRecords\Pages\CreateAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Pages\EditAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Pages\ListAttendanceRecords;
use App\Filament\Resources\AttendanceRecords\Pages\ViewAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordForm;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordInfolist;
use App\Filament\Resources\AttendanceRecords\Tables\AttendanceRecordsTable;
use App\Models\AttendanceRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static ?string $modelLabel = 'Presensi Siswa';

    protected static ?string $pluralModelLabel = 'Presensi Kelas';

    protected static ?string $navigationLabel = 'Presensi Kelas';

    protected static string|\UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-fact-check-o';

    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->hasAnyRole(['guru', 'siswa']) ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->whereIn('student_id', $user?->accessibleStudents()->select('students.id') ?? []);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view attendances') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasRole('orang_tua') ?? false));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage attendances') ?? false)
            && ($user?->isAdmin() ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return (auth()->user()?->isAdmin() ?? false)
            && (auth()->user()?->can('manage attendances') ?? false)
            && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRecordsTable::configure($table);
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
            'index' => ListAttendanceRecords::route('/'),
            'create' => CreateAttendanceRecord::route('/create'),
            'view' => ViewAttendanceRecord::route('/{record}'),
            'edit' => EditAttendanceRecord::route('/{record}/edit'),
        ];
    }
}

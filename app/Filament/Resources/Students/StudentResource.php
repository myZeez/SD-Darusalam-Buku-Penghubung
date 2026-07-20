<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Schemas\StudentInfolist;
use App\Filament\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $modelLabel = 'Siswa';

    protected static ?string $pluralModelLabel = 'Siswa';

    protected static ?string $navigationLabel = 'Data Siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sekolah';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-badge-o';

    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->hasRole('siswa') ?? false);
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        return match (true) {
            $user?->hasRole('guru') => 'Siswa Kelas Saya',
            $user?->hasRole('orang_tua') => 'Anak Saya',
            $user?->hasRole('siswa') => 'Profil Saya',
            default => 'Siswa dan Orang Tua',
        };
    }

    public static function getNavigationUrl(): string
    {
        $user = auth()->user();

        if ($user?->hasRole('siswa')) {
            $studentId = $user->student()->value('id');

            if ($studentId) {
                return static::getUrl('view', ['record' => $studentId]);
            }
        }

        return parent::getNavigationUrl();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return $user->hasRole('guru')
            ? $user->managedStudents()
            : $user->accessibleStudents();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view students') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (! ($user?->can('view students') ?? false)) {
            return false;
        }

        return $user->hasRole('guru')
            ? $user->managedStudents()->whereKey($record)->exists()
            : $user->canAccessStudent($record);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage students') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasRole('guru') ?? false));
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return static::canCreate()
            && ($user?->isAdmin() || $user?->managedStudents()->whereKey($record)->exists());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canManageClass(int $classId): bool
    {
        $user = auth()->user();

        return static::canCreate()
            && ($user?->isAdmin() || $user?->managedClasses()->whereKey($classId)->exists());
    }

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StudentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
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
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}

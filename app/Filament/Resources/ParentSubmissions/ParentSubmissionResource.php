<?php

namespace App\Filament\Resources\ParentSubmissions;

use App\Filament\Resources\ParentSubmissions\Pages\CreateParentSubmission;
use App\Filament\Resources\ParentSubmissions\Pages\EditParentSubmission;
use App\Filament\Resources\ParentSubmissions\Pages\ListParentSubmissions;
use App\Filament\Resources\ParentSubmissions\Pages\ViewParentSubmission;
use App\Filament\Resources\ParentSubmissions\Schemas\ParentSubmissionForm;
use App\Filament\Resources\ParentSubmissions\Schemas\ParentSubmissionInfolist;
use App\Filament\Resources\ParentSubmissions\Tables\ParentSubmissionsTable;
use App\Models\ParentSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ParentSubmissionResource extends Resource
{
    protected static ?string $model = ParentSubmission::class;

    protected static ?string $modelLabel = 'Pengajuan Orang Tua';

    protected static ?string $pluralModelLabel = 'Pengajuan Orang Tua';

    protected static ?string $navigationLabel = 'Pengajuan Orang Tua';

    protected static string|\UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-approval-o';

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return 'Monitoring Izin';
        }

        return $user?->hasRole('orang_tua') ? 'Izin Siswa' : 'Pengajuan Orang Tua';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if ($user?->hasRole('guru')) {
            return $query->whereIn('student_id', $user->managedStudents()->select('students.id'));
        }

        if ($user?->hasRole('orang_tua')) {
            return $query->whereIn('student_id', $user->accessibleStudents()->select('students.id'));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view parent submissions') ?? false)
            && (($user?->isAdmin() ?? false) || ($user?->hasAnyRole(['guru', 'orang_tua']) ?? false));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage parent submissions') ?? false)
            && ($user?->hasRole('orang_tua') ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage parent submissions') ?? false)
            && ($user?->hasRole('orang_tua') ?? false)
            && $record->status === 'pending'
            && $record->parent?->user_id === $user->id;
    }

    public static function canReview(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage parent submissions') ?? false)
            && ($user?->hasRole('guru') ?? false)
            && static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ParentSubmissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParentSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentSubmissionsTable::configure($table);
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
            'index' => ListParentSubmissions::route('/'),
            'create' => CreateParentSubmission::route('/create'),
            'view' => ViewParentSubmission::route('/{record}'),
            'edit' => EditParentSubmission::route('/{record}/edit'),
        ];
    }
}

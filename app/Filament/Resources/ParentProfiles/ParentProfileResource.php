<?php

namespace App\Filament\Resources\ParentProfiles;

use App\Filament\Resources\ParentProfiles\Pages\CreateParentProfile;
use App\Filament\Resources\ParentProfiles\Pages\EditParentProfile;
use App\Filament\Resources\ParentProfiles\Pages\ListParentProfiles;
use App\Filament\Resources\ParentProfiles\Pages\ViewParentProfile;
use App\Filament\Resources\ParentProfiles\Schemas\ParentProfileForm;
use App\Filament\Resources\ParentProfiles\Schemas\ParentProfileInfolist;
use App\Filament\Resources\ParentProfiles\Tables\ParentProfilesTable;
use App\Models\ParentProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ParentProfileResource extends Resource
{
    protected static ?string $model = ParentProfile::class;

    protected static ?string $modelLabel = 'Orang Tua';

    protected static ?string $pluralModelLabel = 'Orang Tua';

    protected static ?string $navigationLabel = 'Data Orang Tua';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Sekolah';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-family-restroom-o';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return ($user?->can('view parents') ?? false)
            || ($user?->can('manage parents') ?? false)
            || ($user?->hasRole('orang_tua') && $user->parentProfile()->exists());
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('orang_tua') ? 'Profil Orang Tua' : 'Data Orang Tua';
    }

    public static function getNavigationUrl(): string
    {
        $user = auth()->user();

        if ($user?->hasRole('orang_tua')) {
            $profileId = $user->parentProfile()->value('id');

            if ($profileId) {
                return static::getUrl('view', ['record' => $profileId]);
            }
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
        $user = auth()->user();

        return ($user?->can('view parents') ?? false)
            || ($user?->can('manage parents') ?? false)
            || ($user?->hasRole('orang_tua') ?? false);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return (($user?->can('view parents') ?? false) && ($user?->isAdmin() ?? false))
            || ($user?->can('manage parents') ?? false)
            || ($user?->hasRole('orang_tua') && $record->user_id === $user->id);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage parents') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage parents') ?? false)
            || ($user?->hasRole('orang_tua') && $record->user_id === $user->id);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage parents') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('manage parents') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ParentProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParentProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentProfilesTable::configure($table);
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
            'index' => ListParentProfiles::route('/'),
            'create' => CreateParentProfile::route('/create'),
            'view' => ViewParentProfile::route('/{record}'),
            'edit' => EditParentProfile::route('/{record}/edit'),
        ];
    }
}

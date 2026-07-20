<?php

namespace App\Filament\Resources\SchoolSettings;

use App\Filament\Resources\SchoolSettings\Pages\CreateSchoolSetting;
use App\Filament\Resources\SchoolSettings\Pages\EditSchoolSetting;
use App\Filament\Resources\SchoolSettings\Pages\ListSchoolSettings;
use App\Filament\Resources\SchoolSettings\Pages\ViewSchoolSetting;
use App\Filament\Resources\SchoolSettings\Schemas\SchoolSettingForm;
use App\Filament\Resources\SchoolSettings\Schemas\SchoolSettingInfolist;
use App\Filament\Resources\SchoolSettings\Tables\SchoolSettingsTable;
use App\Models\SchoolSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchoolSettingResource extends Resource
{
    protected static ?string $model = SchoolSetting::class;

    protected static ?string $modelLabel = 'Pengaturan Sekolah';

    protected static ?string $pluralModelLabel = 'Pengaturan Sekolah';

    protected static ?string $navigationLabel = 'Pengaturan Sekolah';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-admin-panel-settings-o';

    public static function getNavigationUrl(): string
    {
        $setting = SchoolSetting::query()->first();

        if (! $setting) {
            return static::canCreate() ? static::getUrl('create') : parent::getNavigationUrl();
        }

        return static::getUrl(static::canEdit($setting) ? 'edit' : 'view', ['record' => $setting]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view school settings') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return (auth()->user()?->can('manage school settings') ?? false)
            && ! SchoolSetting::query()->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('manage school settings') ?? false;
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
        return SchoolSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchoolSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolSettingsTable::configure($table);
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
            'index' => ListSchoolSettings::route('/'),
            'create' => CreateSchoolSetting::route('/create'),
            'view' => ViewSchoolSetting::route('/{record}'),
            'edit' => EditSchoolSetting::route('/{record}/edit'),
        ];
    }
}

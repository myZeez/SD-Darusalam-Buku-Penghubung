<?php

namespace App\Filament\Resources\PasswordResetRequests;

use App\Filament\Resources\PasswordResetRequests\Pages\ListPasswordResetRequests;
use App\Filament\Resources\PasswordResetRequests\Pages\ViewPasswordResetRequest;
use App\Filament\Resources\PasswordResetRequests\Schemas\PasswordResetRequestInfolist;
use App\Filament\Resources\PasswordResetRequests\Tables\PasswordResetRequestsTable;
use App\Models\PasswordResetRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PasswordResetRequestResource extends Resource
{
    protected static ?string $model = PasswordResetRequest::class;

    protected static ?string $modelLabel = 'Permintaan Sandi';

    protected static ?string $pluralModelLabel = 'Permintaan Sandi';

    protected static ?string $navigationLabel = 'Permintaan Sandi';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-lock-reset-o';

    public static function getNavigationBadge(): ?string
    {
        $pending = PasswordResetRequest::query()->where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view password reset requests') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canProcess(): bool
    {
        return auth()->user()?->can('manage password reset requests') ?? false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return PasswordResetRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PasswordResetRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPasswordResetRequests::route('/'),
            'view' => ViewPasswordResetRequest::route('/{record}'),
        ];
    }
}

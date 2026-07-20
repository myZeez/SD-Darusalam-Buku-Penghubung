<?php

namespace App\Filament\Resources\UserNotifications;

use App\Filament\Resources\UserNotifications\Pages\CreateUserNotification;
use App\Filament\Resources\UserNotifications\Pages\EditUserNotification;
use App\Filament\Resources\UserNotifications\Pages\ListUserNotifications;
use App\Filament\Resources\UserNotifications\Pages\ViewUserNotification;
use App\Filament\Resources\UserNotifications\Schemas\UserNotificationForm;
use App\Filament\Resources\UserNotifications\Schemas\UserNotificationInfolist;
use App\Filament\Resources\UserNotifications\Tables\UserNotificationsTable;
use App\Models\User;
use App\Models\UserNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserNotificationResource extends Resource
{
    protected static ?string $model = UserNotification::class;

    protected static ?string $modelLabel = 'Notifikasi';

    protected static ?string $pluralModelLabel = 'Notifikasi';

    protected static ?string $navigationLabel = 'Notifikasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Informasi';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-notifications-o';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || ! static::canViewAny()) {
            return null;
        }

        $unread = UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Notifikasi yang belum dibaca';
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
                    ->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query->where('user_id', $user?->id ?? 0);
    }

    public static function scopeRecipientQuery(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if (! $user?->hasRole('guru')) {
            return $query->whereRaw('1 = 0');
        }

        $studentIds = $user->managedStudents()->pluck('students.id');

        return $query->where(function (Builder $query) use ($studentIds): void {
            $query
                ->whereHas('student', fn (Builder $query): Builder => $query->whereIn('students.id', $studentIds))
                ->orWhereHas('parentProfile.students', fn (Builder $query): Builder => $query->whereIn('students.id', $studentIds));
        });
    }

    public static function canNotifyUser(int $userId): bool
    {
        return static::scopeRecipientQuery(User::query())->whereKey($userId)->exists();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view notifications') ?? false)
            && ! ($user?->hasRole('siswa') ?? false);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('view notifications') ?? false)
            && (($user?->isAdmin() ?? false) || $record->user_id === $user?->id || $record->created_by === $user?->id);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage notifications') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('manage notifications') ?? false)
            && ($user->isAdmin() || $record->created_by === $user->id);
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
        return UserNotificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserNotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserNotificationsTable::configure($table);
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
            'index' => ListUserNotifications::route('/'),
            'create' => CreateUserNotification::route('/create'),
            'view' => ViewUserNotification::route('/{record}'),
            'edit' => EditUserNotification::route('/{record}/edit'),
        ];
    }
}

@php
    use App\Filament\Resources\UserNotifications\UserNotificationResource;

    $user = auth()->user();
    $canViewNotifications = $user && UserNotificationResource::canViewAny();
    $unreadNotifications = $canViewNotifications
        ? $user->userNotifications()->where('is_read', false)->count()
        : 0;
@endphp

@if ($canViewNotifications)
    <x-filament::icon-button
        tag="a"
        :href="UserNotificationResource::getUrl()"
        :badge="$unreadNotifications ?: null"
        badge-color="danger"
        color="gray"
        icon="gmdi-notifications-o"
        icon-size="lg"
        label="Buka notifikasi"
        tooltip="Notifikasi"
        class="topbar-notifications-button"
    />
@endif

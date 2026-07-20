@props([
    'groups' => [],
    'compact' => false,
])

@php
    $groups = \App\Support\ActivityGroups::normalize($groups);
@endphp

<div @class(['activity-groups', 'activity-groups--compact' => $compact])>
    @forelse ($groups as $group)
        <section class="activity-group">
            <header class="activity-group__header">
                <x-filament::icon icon="gmdi-folder-open-o" />
                <h3>{{ $group['category'] }}</h3>
                <span>{{ count($group['items']) }} aktivitas</span>
            </header>

            <div class="activity-group__items">
                @foreach ($group['items'] as $item)
                    <div @class([
                        'activity-group__item',
                        'activity-group__item--checked' => $item['type'] === 'checklist' && $item['checked'],
                    ])>
                        @if ($item['type'] === 'checklist')
                            <x-filament::icon :icon="$item['checked'] ? 'gmdi-check-circle-o' : 'gmdi-radio-button-unchecked-o'" />
                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                <span>{{ $item['checked'] ? 'Sudah dilakukan' : 'Belum dilakukan' }}</span>
                            </div>
                        @else
                            <x-filament::icon icon="gmdi-notes-o" />
                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                <p>{{ filled($item['text']) ? $item['text'] : 'Belum ada catatan.' }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="activity-groups__empty">
            <x-filament::icon icon="gmdi-playlist-add-o" />
            <p>Belum ada kategori aktivitas pada laporan ini.</p>
        </div>
    @endforelse
</div>

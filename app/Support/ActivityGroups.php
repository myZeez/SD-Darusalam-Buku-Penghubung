<?php

namespace App\Support;

class ActivityGroups
{
    /** @return array<int, array{category: string, items: array<int, array<string, mixed>>}> */
    public static function normalize(mixed $groups): array
    {
        if (! is_array($groups)) {
            return [];
        }

        return collect($groups)
            ->filter(fn (mixed $group): bool => is_array($group))
            ->map(function (array $group): array {
                $items = collect($group['items'] ?? [])
                    ->filter(fn (mixed $item): bool => is_array($item))
                    ->map(function (array $item): array {
                        $type = ($item['type'] ?? 'checklist') === 'text' ? 'text' : 'checklist';

                        return [
                            'label' => trim((string) ($item['label'] ?? '')),
                            'type' => $type,
                            'checked' => $type === 'checklist' && (bool) ($item['checked'] ?? false),
                            'text' => $type === 'text' ? trim((string) ($item['text'] ?? '')) : '',
                        ];
                    })
                    ->filter(fn (array $item): bool => filled($item['label']))
                    ->values()
                    ->all();

                return [
                    'category' => trim((string) ($group['category'] ?? '')),
                    'items' => $items,
                ];
            })
            ->filter(fn (array $group): bool => filled($group['category']) && filled($group['items']))
            ->values()
            ->all();
    }

    public static function categoryNames(array $groups): string
    {
        $categories = collect(self::normalize($groups))
            ->pluck('category')
            ->filter()
            ->join(', ');

        return $categories ?: 'Belum ada kategori';
    }
}

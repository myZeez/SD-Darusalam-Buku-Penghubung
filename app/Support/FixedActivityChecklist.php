<?php

namespace App\Support;

class FixedActivityChecklist
{
    /**
     * @param  array<int, array<string, mixed>>  $template
     * @param  array<int, array<string, mixed>>  $storedGroups
     * @return array<string, bool>
     */
    public static function checksFrom(array $template, array $storedGroups): array
    {
        $storedChecks = collect(ActivityGroups::normalize($storedGroups))
            ->flatMap(fn (array $group) => collect($group['items'])
                ->mapWithKeys(fn (array $item): array => [
                    $item['key'] ?: $item['label'] => (bool) $item['checked'],
                ]))
            ->all();

        return collect(ActivityGroups::normalize($template))
            ->flatMap(fn (array $group) => collect($group['items'])
                ->mapWithKeys(fn (array $item): array => [
                    $item['key'] => (bool) (
                        $storedChecks[$item['key']]
                        ?? $storedChecks[$item['label']]
                        ?? false
                    ),
                ]))
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $template
     * @param  array<string, bool|int|string|null>  $checks
     * @return array<int, array<string, mixed>>
     */
    public static function apply(array $template, array $checks): array
    {
        return collect(ActivityGroups::normalize($template))
            ->map(function (array $group) use ($checks): array {
                $group['items'] = collect($group['items'])
                    ->map(function (array $item) use ($checks): array {
                        $item['type'] = 'checklist';
                        $item['checked'] = (bool) ($checks[$item['key']] ?? false);
                        $item['text'] = '';

                        return $item;
                    })
                    ->values()
                    ->all();

                return $group;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $template
     * @return array<int, array<string, mixed>>
     */
    public static function items(array $template): array
    {
        return collect(ActivityGroups::normalize($template))
            ->flatMap(fn (array $group) => $group['items'])
            ->values()
            ->all();
    }
}

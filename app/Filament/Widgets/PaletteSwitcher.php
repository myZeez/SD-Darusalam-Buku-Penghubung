<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PaletteSwitcher extends Widget
{
    // Theme preference is secondary to the day's information, so it belongs at the end of the dashboard.
    protected static ?int $sort = 100;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** @var view-string */
    protected string $view = 'filament.widgets.palette-switcher';
}

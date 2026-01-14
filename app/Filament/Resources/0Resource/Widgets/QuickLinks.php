<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickLinks extends Widget
{
    protected static string $view = 'filament.widgets.quick-links';

    protected int | string | array $columnSpan = 'full';
}

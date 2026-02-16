<?php

namespace App\Filament\Driver\Widgets;

use Filament\Widgets\Widget;

class DriverQuickLinks extends Widget
{
    protected static string $view = 'filament.driver.widgets.driver-quick-links';

    protected int|string|array $columnSpan = 'full';
}

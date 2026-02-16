<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DriverPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('driver')
            ->path('driver')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
                'gray' => Color::Slate,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
            ])

            ->brandName('Driver Panel')
            ->favicon('/images/favicon.png')
            ->darkMode(true)
            ->darkModeBrandLogo('/images/logo-dark.svg')
            ->sidebarCollapsibleOnDesktop()

            ->discoverResources(in: app_path('Filament/Driver/Resources'), for: 'App\\Filament\\Driver\\Resources')
            ->discoverPages(in: app_path('Filament/Driver/Pages'), for: 'App\\Filament\\Driver\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Driver/Widgets'), for: 'App\\Filament\\Driver\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->authGuard('web')

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook('panels::head.end', fn (): string => <<<'HTML'
                <!-- PWA Manifest -->
                <link rel="manifest" href="/manifest.json">
                <meta name="theme-color" content="#10b981">
                <link rel="apple-touch-icon" href="/images/logo-jalldev.png">

                <!-- Service Worker Registration -->
                <script>
                    if ('serviceWorker' in navigator) {
                        window.addEventListener('load', () => {
                            navigator.serviceWorker.register('/service-worker.js')
                                .then(reg => console.log('SW Registered!', reg.scope))
                                .catch(err => console.log('SW Failed:', err));
                        });
                    }
                </script>
            HTML);
    }
}

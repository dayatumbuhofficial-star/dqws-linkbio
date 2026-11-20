<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\BioPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    protected function resolveDynamicBrandLogo(): ?string
    {
        try {
            if (! Schema::hasTable('bio_pages')) {
                return null;
            }

            $page = BioPage::where('slug', 'dqws')->first();
            if (! $page) {
                return null;
            }

            $logoPath = $page->login_logo ?: null;
            if (! $logoPath) {
                return null;
            }

            if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
                return $logoPath;
            }

            return Storage::disk('public')->url($logoPath);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveDynamicFavicon(): ?string
    {
        try {
            if (! Schema::hasTable('bio_pages')) {
                return null;
            }

            $page = BioPage::where('slug', 'dqws')->first();
            if (! $page) {
                return null;
            }

            $iconPath = $page->login_logo ?: ($page->avatar ?: null);
            if (! $iconPath) {
                return null;
            }

            if (str_starts_with($iconPath, 'http://') || str_starts_with($iconPath, 'https://')) {
                return $iconPath;
            }

            return Storage::disk('public')->url($iconPath);
        } catch (\Throwable) {
            return null;
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('DQWS Link Bio')
            ->brandLogo(fn (): ?string => $this->resolveDynamicBrandLogo())
            ->brandLogoHeight('3rem')
            ->favicon(fn (): ?string => $this->resolveDynamicFavicon())
            ->colors([
                'primary' => Color::Emerald,
                'success' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Profil & Pengaturan')
                    ->collapsed(false),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => <<<'HTML'
                <style>
                    /* DQWS Emerald Tone & Elegant Login Styling */
                    .fi-simple-layout {
                        background: radial-gradient(circle at 50% 0%, rgba(16, 185, 129, 0.20) 0%, rgba(6, 78, 59, 0.55) 45%, #022c22 100%) !important;
                        min-height: 100vh;
                    }
                    .fi-simple-main {
                        background-color: rgba(15, 23, 42, 0.85) !important;
                        backdrop-filter: blur(16px);
                        border: 1px solid rgba(16, 185, 129, 0.28) !important;
                        box-shadow: 0 25px 50px -12px rgba(2, 44, 34, 0.75), 0 0 30px -5px rgba(16, 185, 129, 0.15) !important;
                        border-radius: 1.25rem !important;
                    }
                    .fi-simple-header-heading {
                        color: #f8fafc !important;
                    }
                    .fi-simple-header .fi-logo {
                        height: 3.75rem !important;
                        max-height: 3.75rem !important;
                        width: auto !important;
                        object-fit: contain;
                        margin-bottom: 0.5rem;
                        filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.25));
                    }
                    /* Subtle DQWS Emerald Accents in Admin Panel */
                    .fi-sidebar-header {
                        border-bottom: 1px solid rgba(16, 185, 129, 0.12);
                    }
                    .fi-sidebar-item-active > a {
                        background: linear-gradient(90deg, rgba(16, 185, 129, 0.16) 0%, rgba(6, 78, 59, 0.08) 100%) !important;
                        border-left: 3px solid #10b981;
                    }
                </style>
                HTML
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

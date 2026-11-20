<?php

namespace App\Filament\Pages;

use App\Models\BioPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class BioTrackingSeo extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Profil & Pengaturan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Tracking & SEO';

    protected static ?string $title = 'Tracking Pixel & Tag Manager';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.bio-settings-form';

    public ?array $data = [];

    public ?BioPage $bioPage = null;

    public function mount(): void
    {
        $this->bioPage = BioPage::firstOrCreate(
            ['slug' => 'dqws'],
            [
                'title' => 'Darul Quran Was Sunnah (DQWS)',
                'bio' => 'Lembaga Pendidikan & Dakwah Islam',
                'footer_text' => '© 2026 DQWS Link-in-Bio',
                'draft_theme' => BioPage::defaultTheme(),
                'published_theme' => BioPage::defaultTheme(),
                'draft_blocks' => [],
                'published_blocks' => [],
                'is_published' => true,
            ]
        );

        $draftTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->draft_theme ?? []);

        $this->form->fill([
            'meta_pixel_id' => $draftTheme['meta_pixel_id'] ?? '',
            'tiktok_pixel_id' => $draftTheme['tiktok_pixel_id'] ?? '',
            'gtm_id' => $draftTheme['gtm_id'] ?? '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Integrasi Pixel & Google Tag Manager')
                    ->description('Masukkan ID pelacakan iklan. Script hanya akan dimuat di halaman publik apabila ID diisi.')
                    ->schema([
                        TextInput::make('meta_pixel_id')
                            ->label('Meta (Facebook) Pixel ID')
                            ->placeholder('Contoh: 123456789012345')
                            ->helperText('Otomatis melacak PageView dan event Lead saat tombol/banner diklik.')
                            ->maxLength(64),

                        TextInput::make('tiktok_pixel_id')
                            ->label('TikTok Pixel ID')
                            ->placeholder('Contoh: C123456789ABCDEF')
                            ->helperText('Otomatis melacak PageView dan event ClickButton.')
                            ->maxLength(64),

                        TextInput::make('gtm_id')
                            ->label('Google Tag Manager (GTM) ID')
                            ->placeholder('Contoh: GTM-XXXXXXX')
                            ->helperText('Memasang container GTM di <head> & <body> serta mengirim event bio_click ke dataLayer.')
                            ->maxLength(64),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveAndPublish')
                ->label('Simpan & Publish')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->action('saveAndPublish'),

            Action::make('openPublic')
                ->label('Buka Halaman Publik')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => url('/'), shouldOpenInNewTab: true),
        ];
    }

    public function saveAndPublish(): void
    {
        $state = $this->form->getState();
        $this->bioPage->refresh();

        $metaPixelId = trim((string) ($state['meta_pixel_id'] ?? ''));
        $tiktokPixelId = trim((string) ($state['tiktok_pixel_id'] ?? ''));
        $gtmId = trim((string) ($state['gtm_id'] ?? ''));

        $draftTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->draft_theme ?? []);
        $publishedTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->published_theme ?? []);

        $draftTheme['meta_pixel_id'] = $metaPixelId;
        $draftTheme['tiktok_pixel_id'] = $tiktokPixelId;
        $draftTheme['gtm_id'] = $gtmId;

        $publishedTheme['meta_pixel_id'] = $metaPixelId;
        $publishedTheme['tiktok_pixel_id'] = $tiktokPixelId;
        $publishedTheme['gtm_id'] = $gtmId;

        $this->bioPage->update([
            'draft_theme' => $draftTheme,
            'published_theme' => $publishedTheme,
        ]);

        Notification::make()
            ->title('Pengaturan Tracking & SEO berhasil disimpan!')
            ->body('ID Pixel dan GTM telah diperbarui dan diterbitkan ke halaman publik.')
            ->success()
            ->send();
    }
}

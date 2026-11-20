<?php

namespace App\Filament\Pages;

use App\Models\BioPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class BioSocialIcons extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Profil & Pengaturan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationLabel = 'Ikon Sosial Media';

    protected static ?string $title = 'Ikon Sosial Media DQWS';

    protected static ?int $navigationSort = 3;

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
            'social_icons' => is_array($draftTheme['social_icons'] ?? null) ? array_values($draftTheme['social_icons']) : [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Baris Ikon Sosial Media (Social Icons Bar)')
                    ->description('Ikon sosial media ditampilkan berderet rapi di bagian bawah halaman (di bawah daftar tombol/blok dan tepat di atas teks footer).')
                    ->schema([
                        Repeater::make('social_icons')
                            ->label('Daftar Akun Sosial Media')
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('Tambah Ikon Sosial Media')
                            ->default([])
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('platform')
                                            ->label('Platform')
                                            ->options([
                                                'instagram' => 'Instagram',
                                                'youtube' => 'YouTube',
                                                'tiktok' => 'TikTok',
                                                'facebook' => 'Facebook',
                                                'whatsapp' => 'WhatsApp',
                                                'telegram' => 'Telegram',
                                                'website' => 'Website',
                                                'maps' => 'Google Maps',
                                            ])
                                            ->default('instagram')
                                            ->required(),

                                        TextInput::make('url')
                                            ->label('URL Tujuan')
                                            ->placeholder('https://...')
                                            ->required(),
                                    ]),
                            ]),
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

        $socialIcons = [];
        foreach (array_values($state['social_icons'] ?? []) as $soc) {
            if (! is_array($soc) || empty($soc['url'])) {
                continue;
            }
            $soc['url'] = BioPage::normalizeUrl($soc['url']);
            $socialIcons[] = $soc;
        }

        $draftTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->draft_theme ?? []);
        $publishedTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->published_theme ?? []);

        $draftTheme['social_icons'] = $socialIcons;
        $publishedTheme['social_icons'] = $socialIcons;

        $this->bioPage->update([
            'draft_theme' => $draftTheme,
            'published_theme' => $publishedTheme,
        ]);

        Notification::make()
            ->title('Ikon Sosial Media berhasil disimpan!')
            ->body('Daftar ikon sosial media telah diperbarui di halaman publik dan Live Preview.')
            ->success()
            ->send();
    }
}

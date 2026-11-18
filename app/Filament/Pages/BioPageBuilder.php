<?php

namespace App\Filament\Pages;

use App\Models\BioPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BioPageBuilder extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Page Builder DQWS';

    protected static ?string $title = 'Page Builder DQWS';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.bio-page-builder';

    protected Width | string | null $maxContentWidth = Width::Full;

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
        $draftBlocks = BioPage::ensureBlockIds($this->bioPage->draft_blocks ?? []);

        if ($draftBlocks !== ($this->bioPage->draft_blocks ?? [])) {
            $publishedBlocks = BioPage::ensureBlockIds($this->bioPage->published_blocks ?? []);
            $this->bioPage->update([
                'draft_blocks' => $draftBlocks,
                'published_blocks' => $publishedBlocks,
            ]);
        }

        $this->form->fill([
            'avatar' => $this->bioPage->avatar,
            'title' => $this->bioPage->title,
            'bio' => $this->bioPage->bio,
            'footer_text' => $draftTheme['footer_text'] ?? $this->bioPage->footer_text ?? '© 2026 DQWS Link-in-Bio',
            'theme' => $draftTheme,
            'blocks' => $draftBlocks,
        ]);
    }

    protected function scheduleFields(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    DateTimePicker::make('start_at')
                        ->label('Mulai Tayang (Opsional - WIB)')
                        ->helperText('Kosongkan jika ingin langsung tayang.')
                        ->seconds(false)
                        ->live(),
                    DateTimePicker::make('end_at')
                        ->label('Selesai Tayang (Opsional - WIB)')
                        ->helperText('Kosongkan jika tanpa batas waktu.')
                        ->afterOrEqual('start_at')
                        ->seconds(false)
                        ->live(),
                ]),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('BuilderTabs')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Profil')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                FileUpload::make('avatar')
                                    ->label('Avatar / Logo DQWS')
                                    ->image()
                                    ->avatar()
                                    ->disk('public')
                                    ->directory('avatars')
                                    ->visibility('public')
                                    ->live(),
                                TextInput::make('title')
                                    ->label('Judul Halaman')
                                    ->required()
                                    ->maxLength(150)
                                    ->live(debounce: 250),
                                Textarea::make('bio')
                                    ->label('Bio Singkat')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->live(debounce: 250),
                                TextInput::make('footer_text')
                                    ->label('Teks Footer')
                                    ->default('© 2026 DQWS Link-in-Bio')
                                    ->maxLength(150)
                                    ->live(debounce: 250),
                                Toggle::make('theme.show_search')
                                    ->label('Tampilkan Kolom Pencarian (Search) di Halaman Publik')
                                    ->default(true)
                                    ->live(),
                                Repeater::make('theme.social_icons')
                                    ->label('Baris Ikon Sosial Media (Social Icons Bar)')
                                    ->reorderable()
                                    ->collapsible()
                                    ->addActionLabel('Tambah Ikon Sosial Media')
                                    ->default([])
                                    ->live()
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
                                                    ->required()
                                                    ->live(),
                                                TextInput::make('url')
                                                    ->label('URL Tujuan')
                                                    ->placeholder('https://...')
                                                    ->live(debounce: 250),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Desain & Tema')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('theme.bg_type')
                                            ->label('Tipe Background')
                                            ->options([
                                                'solid' => 'Solid (Satu Warna)',
                                                'gradient' => 'Gradient (Gradasi)',
                                            ])
                                            ->default('gradient')
                                            ->required()
                                            ->live(),
                                        Select::make('theme.button_style')
                                            ->label('Gaya Tombol')
                                            ->options([
                                                'rounded-full' => 'Pill (Bulat Penuh)',
                                                'rounded-xl' => 'Rounded (Sudut Halus)',
                                                'rounded-none' => 'Kotak (Tajam)',
                                            ])
                                            ->default('rounded-xl')
                                            ->required()
                                            ->live(),
                                        ColorPicker::make('theme.bg_color_1')
                                            ->label('Warna Background 1')
                                            ->required()
                                            ->live(),
                                        ColorPicker::make('theme.bg_color_2')
                                            ->label('Warna Background 2')
                                            ->visible(fn (Get $get): bool => $get('theme.bg_type') === 'gradient')
                                            ->required()
                                            ->live(),
                                        ColorPicker::make('theme.text_color')
                                            ->label('Warna Teks')
                                            ->required()
                                            ->live(),
                                        ColorPicker::make('theme.button_color')
                                            ->label('Warna Tombol')
                                            ->required()
                                            ->live(),
                                        ColorPicker::make('theme.button_text_color')
                                            ->label('Warna Teks Tombol')
                                            ->required()
                                            ->live(),
                                    ]),
                            ]),

                        Tab::make('Blok & Tautan')
                            ->icon('heroicon-o-squares-plus')
                            ->schema([
                                Builder::make('blocks')
                                    ->label('Susunan Blok Konten')
                                    ->reorderable()
                                    ->collapsible()
                                    ->cloneable()
                                    ->addActionLabel('Tambah Blok')
                                    ->live()
                                    ->blocks([
                                        Block::make('link')
                                            ->label('Tombol Link')
                                            ->icon('heroicon-o-link')
                                            ->schema([
                                                Hidden::make('id')
                                                    ->default(fn (): string => (string) Str::uuid()),
                                                TextInput::make('label')
                                                    ->label('Label Tombol')
                                                    ->default('Tautan Baru')
                                                    ->live(debounce: 250),
                                                TextInput::make('url')
                                                    ->label('URL Tujuan')
                                                    ->placeholder('https://...')
                                                    ->live(debounce: 250),
                                                Toggle::make('highlight')
                                                    ->label('Highlight (Aksen Khusus)')
                                                    ->default(false)
                                                    ->live(),
                                                ...$this->scheduleFields(),
                                            ]),

                                        Block::make('whatsapp')
                                            ->label('Tombol WhatsApp')
                                            ->icon('heroicon-o-chat-bubble-left-right')
                                            ->schema([
                                                Hidden::make('id')
                                                    ->default(fn (): string => (string) Str::uuid()),
                                                TextInput::make('label')
                                                    ->label('Label Tombol')
                                                    ->default('Hubungi via WhatsApp')
                                                    ->live(debounce: 250),
                                                TextInput::make('phone')
                                                    ->label('Nomor WhatsApp (format 628...)')
                                                    ->placeholder('6281234567890')
                                                    ->live(debounce: 250),
                                                Textarea::make('message')
                                                    ->label('Pesan Otomatis')
                                                    ->rows(2)
                                                    ->placeholder('Assalamualaikum...')
                                                    ->live(debounce: 250),
                                                ...$this->scheduleFields(),
                                            ]),

                                        Block::make('banner')
                                            ->label('Banner Program/Event')
                                            ->icon('heroicon-o-megaphone')
                                            ->schema([
                                                Hidden::make('id')
                                                    ->default(fn (): string => (string) Str::uuid()),
                                                TextInput::make('title')
                                                    ->label('Judul Program / Event')
                                                    ->default('Program DQWS')
                                                    ->live(debounce: 250),
                                                Textarea::make('description')
                                                    ->label('Deskripsi Singkat')
                                                    ->rows(2)
                                                    ->live(debounce: 250),
                                                TextInput::make('url')
                                                    ->label('URL Tujuan')
                                                    ->placeholder('https://...')
                                                    ->live(debounce: 250),
                                                ...$this->scheduleFields(),
                                            ]),

                                        Block::make('carousel')
                                            ->label('Banner Slide (Carousel 650x350)')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                Hidden::make('id')
                                                    ->default(fn (): string => (string) Str::uuid()),
                                                Repeater::make('slides')
                                                    ->label('Daftar Slide Gambar (Ukuran Ideal: 650x350 px)')
                                                    ->reorderable()
                                                    ->collapsible()
                                                    ->addActionLabel('Tambah Slide Gambar')
                                                    ->defaultItems(1)
                                                    ->live()
                                                    ->schema([
                                                        Hidden::make('slide_id')
                                                            ->default(fn (): string => (string) Str::uuid()),
                                                        FileUpload::make('image')
                                                            ->label('Gambar Slide (Rasio 650x350)')
                                                            ->hint('Rasio 13:7 / 650x350 px')
                                                            ->image()
                                                            ->imageEditor()
                                                            ->imageEditorAspectRatios(['13:7'])
                                                            ->disk('public')
                                                            ->directory('slides')
                                                            ->visibility('public')
                                                            ->live(),
                                                        Grid::make(2)
                                                            ->schema([
                                                                TextInput::make('title')
                                                                    ->label('Judul / Alt (Opsional)')
                                                                    ->maxLength(120)
                                                                    ->live(debounce: 250),
                                                                TextInput::make('url')
                                                                    ->label('URL Tujuan')
                                                                    ->placeholder('https://...')
                                                                    ->live(debounce: 250),
                                                            ]),
                                                    ]),
                                                ...$this->scheduleFields(),
                                            ]),

                                        Block::make('section')
                                            ->label('Judul Seksi / Pemisah')
                                            ->icon('heroicon-o-minus')
                                            ->schema([
                                                Hidden::make('id')
                                                    ->default(fn (): string => (string) Str::uuid()),
                                                TextInput::make('title')
                                                    ->label('Teks Kategori / Seksi')
                                                    ->default('Kategori')
                                                    ->live(debounce: 250),
                                                ...$this->scheduleFields(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Pop-up')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Toggle::make('theme.popup_enabled')
                                    ->label('Aktifkan Pop-up Pengumuman / Promo')
                                    ->default(false)
                                    ->live(),
                                FileUpload::make('theme.popup_image')
                                    ->label('Gambar Pop-up (Opsional)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('popups')
                                    ->visibility('public')
                                    ->live(),
                                TextInput::make('theme.popup_title')
                                    ->label('Judul Pop-up')
                                    ->placeholder('Contoh: Pendaftaran Santri Baru Dibuka!')
                                    ->maxLength(150)
                                    ->live(debounce: 250),
                                Textarea::make('theme.popup_message')
                                    ->label('Pesan Singkat')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->live(debounce: 250),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('theme.popup_cta_label')
                                            ->label('Teks Tombol CTA')
                                            ->default('Lihat Selengkapnya')
                                            ->maxLength(80)
                                            ->live(debounce: 250),
                                        TextInput::make('theme.popup_cta_url')
                                            ->label('URL Tombol CTA')
                                            ->placeholder('https://...')
                                            ->live(debounce: 250),
                                        Select::make('theme.popup_frequency')
                                            ->label('Frekuensi Tampil')
                                            ->options([
                                                'always' => 'Setiap Kunjungan (Always)',
                                                'daily' => '1x Per Hari (Via LocalStorage)',
                                            ])
                                            ->default('always')
                                            ->selectablePlaceholder(false)
                                            ->live(),
                                        TextInput::make('theme.popup_delay')
                                            ->label('Delay Muncul (Detik)')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0)
                                            ->maxValue(30)
                                            ->live(),
                                    ]),
                            ]),

                        Tab::make('Tracking & SEO')
                            ->icon('heroicon-o-chart-pie')
                            ->schema([
                                TextInput::make('theme.meta_pixel_id')
                                    ->label('Meta (Facebook) Pixel ID')
                                    ->placeholder('Contoh: 123456789012345')
                                    ->helperText('Otomatis melacak PageView dan event Lead saat tombol/banner diklik.')
                                    ->maxLength(64)
                                    ->live(debounce: 250),
                                TextInput::make('theme.tiktok_pixel_id')
                                    ->label('TikTok Pixel ID')
                                    ->placeholder('Contoh: C123456789ABCDEF')
                                    ->helperText('Otomatis melacak PageView dan event ClickButton.')
                                    ->maxLength(64)
                                    ->live(debounce: 250),
                                TextInput::make('theme.gtm_id')
                                    ->label('Google Tag Manager (GTM) ID')
                                    ->placeholder('Contoh: GTM-XXXXXXX')
                                    ->helperText('Memasang container GTM di <head> & <body> serta mengirim event bio_click ke dataLayer.')
                                    ->maxLength(64)
                                    ->live(debounce: 250),
                            ]),
                    ]),
            ]);
    }

    protected function resolveFileUrl(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = reset($file) ?: null;
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->temporaryUrl();
        }

        if (is_string($file) && $file !== '') {
            if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
                return $file;
            }

            return Storage::disk('public')->url($file);
        }

        return null;
    }

    #[Computed]
    public function avatarUrl(): ?string
    {
        return $this->resolveFileUrl($this->data['avatar'] ?? null);
    }

    #[Computed]
    public function popupImageUrl(): ?string
    {
        return $this->resolveFileUrl($this->data['theme']['popup_image'] ?? null);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action('saveDraft'),

            Action::make('publishChanges')
                ->label('Publish Perubahan')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->action('publishChanges'),

            Action::make('openPublic')
                ->label('Buka Halaman Publik')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn (): string => url('/'), shouldOpenInNewTab: true),
        ];
    }

    /**
     * @return array{title: string, bio: ?string, avatar: ?string, footer_text: string, theme: array, blocks: array}
     */
    protected function extractNormalizedPayload(): array
    {
        $state = $this->form->getState();

        $title = trim((string) ($state['title'] ?? $this->data['title'] ?? 'Darul Quran Was Sunnah (DQWS)'));
        $bio = $state['bio'] ?? $this->data['bio'] ?? null;
        $avatar = BioPage::unwrapFilePath($state['avatar'] ?? $this->data['avatar'] ?? null);
        $footerText = (string) ($state['footer_text'] ?? $this->data['footer_text'] ?? '© 2026 DQWS Link-in-Bio');

        $rawTheme = array_merge(
            BioPage::defaultTheme(),
            is_array($this->data['theme'] ?? null) ? $this->data['theme'] : [],
            is_array($state['theme'] ?? null) ? $state['theme'] : []
        );

        $rawTheme['footer_text'] = $footerText;
        $rawTheme['show_search'] = (bool) ($rawTheme['show_search'] ?? true);
        $rawTheme['popup_enabled'] = (bool) ($rawTheme['popup_enabled'] ?? false);
        $rawTheme['popup_image'] = BioPage::unwrapFilePath($rawTheme['popup_image'] ?? null);
        $rawTheme['popup_cta_url'] = BioPage::normalizeUrl($rawTheme['popup_cta_url'] ?? '');

        $socialIcons = [];
        foreach (array_values($rawTheme['social_icons'] ?? []) as $soc) {
            if (! is_array($soc) || empty($soc['url'])) {
                continue;
            }
            $soc['url'] = BioPage::normalizeUrl($soc['url']);
            $socialIcons[] = $soc;
        }
        $rawTheme['social_icons'] = $socialIcons;

        $rawBlocks = is_array($state['blocks'] ?? null)
            ? $state['blocks']
            : (is_array($this->data['blocks'] ?? null) ? $this->data['blocks'] : []);

        $blocks = BioPage::ensureBlockIds($rawBlocks);

        return [
            'title' => $title,
            'bio' => $bio,
            'avatar' => $avatar,
            'footer_text' => $footerText,
            'theme' => $rawTheme,
            'blocks' => $blocks,
        ];
    }

    public function saveDraft(): void
    {
        $payload = $this->extractNormalizedPayload();

        $this->bioPage->update([
            'title' => $payload['title'],
            'bio' => $payload['bio'],
            'avatar' => $payload['avatar'],
            'footer_text' => $payload['footer_text'],
            'draft_theme' => $payload['theme'],
            'draft_blocks' => $payload['blocks'],
        ]);

        Notification::make()
            ->title('Draft berhasil disimpan')
            ->body('Perubahan tersimpan sebagai draft dan belum dipublish.')
            ->success()
            ->send();
    }

    public function publishChanges(): void
    {
        $payload = $this->extractNormalizedPayload();

        $this->bioPage->update([
            'title' => $payload['title'],
            'bio' => $payload['bio'],
            'avatar' => $payload['avatar'],
            'footer_text' => $payload['footer_text'],
            'draft_theme' => $payload['theme'],
            'published_theme' => $payload['theme'],
            'draft_blocks' => $payload['blocks'],
            'published_blocks' => $payload['blocks'],
            'is_published' => true,
        ]);

        Notification::make()
            ->title('Perubahan berhasil dipublish!')
            ->body('Seluruh blok, tema, dan pengaturan telah diterbitkan ke halaman publik.')
            ->success()
            ->send();
    }
}

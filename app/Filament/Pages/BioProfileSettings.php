<?php

namespace App\Filament\Pages;

use App\Models\BioPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class BioProfileSettings extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Profil & Pengaturan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profil Halaman';

    protected static ?string $title = 'Profil Halaman & Brand DQWS';

    protected static ?int $navigationSort = 2;

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
            'avatar' => $this->bioPage->avatar,
            'login_logo' => $this->bioPage->login_logo,
            'title' => $this->bioPage->title,
            'bio' => $this->bioPage->bio,
            'footer_text' => $draftTheme['footer_text'] ?? $this->bioPage->footer_text ?? '© 2026 DQWS Link-in-Bio',
            'show_search' => (bool) ($draftTheme['show_search'] ?? true),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitas & Logo Brand DQWS')
                    ->description('Atur foto profil halaman publik serta logo resmi yang tampil di halaman Login & Header Admin.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('avatar')
                                    ->label('Avatar / Logo Halaman Publik')
                                    ->helperText('Tampil sebagai foto profil bulat di halaman utama Link-in-Bio.')
                                    ->image()
                                    ->avatar()
                                    ->disk('public')
                                    ->directory('avatars')
                                    ->visibility('public'),

                                FileUpload::make('login_logo')
                                    ->label('Logo Halaman Login & Brand Admin')
                                    ->helperText('Tampil di halaman Login (/admin/login), Pojok Kiri Atas Admin, dan Favicon.')
                                    ->image()
                                    ->disk('public')
                                    ->directory('branding')
                                    ->visibility('public')
                                    ->nullable(),
                            ]),
                    ]),

                Section::make('Informasi Profil & Navigasi')
                    ->description('Informasi utama yang dilihat pengunjung di bagian atas dan bawah halaman.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Halaman')
                            ->required()
                            ->maxLength(150),

                        Textarea::make('bio')
                            ->label('Bio Singkat')
                            ->rows(3)
                            ->maxLength(500),

                        TextInput::make('footer_text')
                            ->label('Teks Footer')
                            ->default('© 2026 DQWS Link-in-Bio')
                            ->maxLength(150),

                        Toggle::make('show_search')
                            ->label('Tampilkan Kolom Pencarian (Search) di Halaman Publik')
                            ->default(true),
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

        $title = trim((string) ($state['title'] ?? 'Darul Quran Was Sunnah (DQWS)'));
        $bio = $state['bio'] ?? null;
        $avatar = BioPage::unwrapFilePath($state['avatar'] ?? null);
        $loginLogo = BioPage::unwrapFilePath($state['login_logo'] ?? null);
        $footerText = (string) ($state['footer_text'] ?? '© 2026 DQWS Link-in-Bio');
        $showSearch = (bool) ($state['show_search'] ?? true);

        $draftTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->draft_theme ?? []);
        $publishedTheme = array_merge(BioPage::defaultTheme(), $this->bioPage->published_theme ?? []);

        $draftTheme['footer_text'] = $footerText;
        $draftTheme['show_search'] = $showSearch;

        $publishedTheme['footer_text'] = $footerText;
        $publishedTheme['show_search'] = $showSearch;

        $this->bioPage->update([
            'title' => $title,
            'bio' => $bio,
            'avatar' => $avatar,
            'login_logo' => $loginLogo,
            'footer_text' => $footerText,
            'draft_theme' => $draftTheme,
            'published_theme' => $publishedTheme,
        ]);

        Notification::make()
            ->title('Profil & Brand berhasil disimpan!')
            ->body('Perubahan profil, logo login, dan pengaturan pencarian telah diterbitkan.')
            ->success()
            ->send();
    }
}

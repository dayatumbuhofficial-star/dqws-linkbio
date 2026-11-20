<?php

namespace App\Filament\Pages;

use App\Models\MediaItem;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

class MediaLibrary extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Media';

    protected static ?string $title = 'Pustaka Media (Multi-Upload Gambar & Video)';

    protected static ?string $slug = 'media';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.media-library';

    protected Width | string | null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public string $filterType = 'all';

    public string $searchQuery = '';

    public function mount(): void
    {
        $this->form->fill([
            'title_prefix' => '',
            'files' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Upload Banyak Media Sekaligus')
                    ->description('Pilih atau tarik banyak file gambar (JPG, PNG, WebP, GIF) maupun video (MP4, WebM) sekaligus untuk disimpan ke pustaka media.')
                    ->schema([
                        TextInput::make('title_prefix')
                            ->label('Judul / Label Media (Opsional)')
                            ->placeholder('Kosongkan untuk menggunakan nama file asli secara otomatis')
                            ->maxLength(120),

                        FileUpload::make('files')
                            ->label('Pilih File Gambar / Video (Bisa Multi-Select)')
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                                'video/mp4',
                                'video/webm',
                            ])
                            ->maxSize(51200)
                            ->maxFiles(30)
                            ->storeFileNamesIn('original_names')
                            ->required(),
                    ]),
            ]);
    }

    public function uploadMedia(): void
    {
        $state = $this->form->getState();
        $files = is_array($state['files'] ?? null) ? array_values($state['files']) : [];
        $originalNames = is_array($state['original_names'] ?? null) ? $state['original_names'] : [];
        $prefix = trim((string) ($state['title_prefix'] ?? ''));

        if (count($files) === 0) {
            Notification::make()
                ->title('Tidak ada file yang dipilih')
                ->warning()
                ->send();

            return;
        }

        $disk = Storage::disk('public');
        $createdCount = 0;

        foreach ($files as $idx => $filePath) {
            if (! is_string($filePath) || $filePath === '') {
                continue;
            }

            $origName = $originalNames[$filePath] ?? basename($filePath);
            $cleanOrigTitle = pathinfo((string) $origName, PATHINFO_FILENAME);

            $title = $prefix !== ''
                ? (count($files) > 1 ? "{$prefix} #" . ($idx + 1) : $prefix)
                : ($cleanOrigTitle ?: basename($filePath));

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $isVideo = in_array($ext, ['mp4', 'webm', 'mov', 'ogg'], true);

            $mimeType = $isVideo ? "video/{$ext}" : "image/{$ext}";
            $fileSize = 0;

            try {
                if ($disk->exists($filePath)) {
                    $fileSize = (int) $disk->size($filePath);
                    $detectedMime = $disk->mimeType($filePath);
                    if ($detectedMime) {
                        $mimeType = $detectedMime;
                        $isVideo = str_starts_with($detectedMime, 'video/');
                    }
                }
            } catch (\Throwable) {
                // Fallback to extension-based detection
            }

            MediaItem::create([
                'title' => $title,
                'file_path' => $filePath,
                'file_type' => $isVideo ? 'video' : 'image',
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);

            $createdCount++;
        }

        $this->form->fill([
            'title_prefix' => '',
            'files' => [],
        ]);

        unset($this->mediaItems);

        Notification::make()
            ->title("{$createdCount} media berhasil diupload!")
            ->body('File telah tersimpan di Pustaka Media dan siap disalin URL-nya.')
            ->success()
            ->send();
    }

    public function deleteMedia(int $id): void
    {
        $item = MediaItem::find($id);
        if (! $item) {
            return;
        }

        try {
            if ($item->file_path && Storage::disk('public')->exists($item->file_path)) {
                Storage::disk('public')->delete($item->file_path);
            }
        } catch (\Throwable) {
            // Ignore file deletion error
        }

        $item->delete();
        unset($this->mediaItems);

        Notification::make()
            ->title('Media berhasil dihapus')
            ->success()
            ->send();
    }

    #[Computed]
    public function mediaItems(): Collection
    {
        $query = MediaItem::query()->latest();

        if (in_array($this->filterType, ['image', 'video'], true)) {
            $query->where('file_type', $this->filterType);
        }

        $search = trim($this->searchQuery);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_path', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }
}

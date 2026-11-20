<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BioPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'bio',
        'avatar',
        'login_logo',
        'footer_text',
        'draft_theme',
        'published_theme',
        'draft_blocks',
        'published_blocks',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'draft_theme' => 'array',
            'published_theme' => 'array',
            'draft_blocks' => 'array',
            'published_blocks' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(BioAnalyticsEvent::class);
    }

    public static function defaultTheme(): array
    {
        return [
            'bg_type' => 'gradient',
            'bg_color_1' => '#064e3b',
            'bg_color_2' => '#0f172a',
            'text_color' => '#f8fafc',
            'button_color' => '#10b981',
            'button_text_color' => '#ffffff',
            'button_style' => 'rounded-xl',
            'footer_text' => '© 2026 DQWS Link-in-Bio',
            'show_search' => true,
            'social_icons' => [],
            'meta_pixel_id' => '',
            'tiktok_pixel_id' => '',
            'gtm_id' => '',
            'popup_enabled' => false,
            'popup_image' => null,
            'popup_title' => '',
            'popup_message' => '',
            'popup_cta_label' => 'Lihat Selengkapnya',
            'popup_cta_url' => '',
            'popup_frequency' => 'always',
            'popup_delay' => 1,
        ];
    }

    public static function normalizeUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || $url === '#') {
            return $url;
        }

        if (
            ! str_starts_with($url, 'http://') &&
            ! str_starts_with($url, 'https://') &&
            ! str_starts_with($url, 'mailto:') &&
            ! str_starts_with($url, 'tel:') &&
            ! str_starts_with($url, '/')
        ) {
            return 'https://' . $url;
        }

        return $url;
    }

    public static function unwrapFilePath(mixed $val): ?string
    {
        if (is_array($val)) {
            $first = reset($val);
            return is_string($first) && $first !== '' ? $first : null;
        }

        return is_string($val) && $val !== '' ? $val : null;
    }

    public static function ensureBlockIds(array $blocks): array
    {
        $normalized = [];

        foreach (array_values($blocks) as $block) {
            if (! is_array($block) || empty($block['type'])) {
                continue;
            }

            $type = (string) $block['type'];
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $id = $data['id'] ?? $block['id'] ?? (string) Str::uuid();
            $data['id'] = (string) $id;

            if (isset($data['url'])) {
                $data['url'] = self::normalizeUrl($data['url']);
            }

            if ($type === 'video') {
                $data['source_type'] = in_array($data['source_type'] ?? '', ['embed', 'upload'], true)
                    ? $data['source_type']
                    : 'embed';
                $data['video_file'] = self::unwrapFilePath($data['video_file'] ?? null);
            }

            if ($type === 'social_embed') {
                $data['platform'] = in_array($data['platform'] ?? '', ['instagram', 'tiktok', 'facebook', 'x'], true)
                    ? $data['platform']
                    : 'instagram';
            }

            if (in_array($type, ['carousel', 'banner_slide'], true)) {
                $rawSlides = is_array($data['slides'] ?? null)
                    ? array_values($data['slides'])
                    : (is_array($block['slides'] ?? null) ? array_values($block['slides']) : []);

                $slides = [];
                foreach ($rawSlides as $slide) {
                    if (! is_array($slide)) {
                        continue;
                    }
                    $slide['slide_id'] = (string) ($slide['slide_id'] ?? Str::uuid());
                    $slide['image'] = self::unwrapFilePath($slide['image'] ?? null);
                    if (isset($slide['url'])) {
                        $slide['url'] = self::normalizeUrl($slide['url']);
                    }
                    $slides[] = $slide;
                }
                $data['slides'] = $slides;
            }

            $block['id'] = (string) $id;
            $block['data'] = $data;

            $normalized[] = $block;
        }

        return $normalized;
    }

    public static function isBlockActive(array $block): bool
    {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $now = Carbon::now('Asia/Jakarta');

        if (! empty($data['start_at'])) {
            try {
                $startAt = Carbon::parse($data['start_at'], 'Asia/Jakarta');
                if ($now->lt($startAt)) {
                    return false;
                }
            } catch (\Throwable) {
                // Ignore invalid date
            }
        }

        if (! empty($data['end_at'])) {
            try {
                $endAt = Carbon::parse($data['end_at'], 'Asia/Jakarta');
                if ($now->gt($endAt)) {
                    return false;
                }
            } catch (\Throwable) {
                // Ignore invalid date
            }
        }

        return true;
    }

    public static function resolveVideoEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || $url === '#') {
            return null;
        }

        // YouTube: watch?v=, youtu.be/, shorts/, embed/, live/
        if (preg_match('/(?:youtube\.com\/(?:watch\?.*v=|shorts\/|embed\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{6,20})/i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Vimeo: vimeo.com/{id} or player.vimeo.com/video/{id}
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/i', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return $url;
    }

    /**
     * @return array{embed_url: ?string, platform_label: string, aspect_class: string}
     */
    public static function resolveSocialEmbed(?string $platform, ?string $url): array
    {
        $platform = strtolower(trim((string) ($platform ?: 'instagram')));
        $url = self::normalizeUrl($url);

        $platformLabel = match ($platform) {
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'facebook' => 'Facebook',
            'x' => 'X (Twitter)',
            default => ucfirst($platform),
        };

        if ($url === '' || $url === '#') {
            return [
                'embed_url' => null,
                'platform_label' => $platformLabel,
                'aspect_class' => 'min-h-[420px]',
            ];
        }

        if ($platform === 'instagram') {
            if (preg_match('/(?:instagram\.com|instagr\.am)\/(p|reel|reels|tv)\/([A-Za-z0-9_-]+)/i', $url, $m)) {
                $kind = strtolower($m[1]) === 'reels' ? 'reel' : strtolower($m[1]);
                return [
                    'embed_url' => "https://www.instagram.com/{$kind}/{$m[2]}/embed",
                    'platform_label' => 'Instagram',
                    'aspect_class' => 'min-h-[480px]',
                ];
            }
        }

        if ($platform === 'tiktok') {
            if (preg_match('/tiktok\.com\/(?:.*\/video\/|embed\/v2\/)(\d+)/i', $url, $m)) {
                return [
                    'embed_url' => "https://www.tiktok.com/embed/v2/{$m[1]}",
                    'platform_label' => 'TikTok',
                    'aspect_class' => 'min-h-[540px]',
                ];
            }
        }

        if ($platform === 'facebook') {
            $isVideo = (bool) preg_match('/(?:\/videos\/|\/reel\/|\/watch|fb\.watch)/i', $url);
            $plugin = $isVideo ? 'video.php' : 'post.php';
            return [
                'embed_url' => "https://www.facebook.com/plugins/{$plugin}?href=" . urlencode($url) . '&show_text=' . ($isVideo ? 'false' : 'true') . '&width=500',
                'platform_label' => 'Facebook',
                'aspect_class' => $isVideo ? 'aspect-video min-h-[260px]' : 'min-h-[420px]',
            ];
        }

        if ($platform === 'x') {
            if (preg_match('/(?:twitter\.com|x\.com)\/[^\/]+\/status\/(\d+)/i', $url, $m)) {
                return [
                    'embed_url' => "https://platform.twitter.com/embed/Tweet.html?id={$m[1]}&theme=dark",
                    'platform_label' => 'X (Twitter)',
                    'aspect_class' => 'min-h-[380px]',
                ];
            }
        }

        return [
            'embed_url' => null,
            'platform_label' => $platformLabel,
            'aspect_class' => 'min-h-[200px]',
        ];
    }
}

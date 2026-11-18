<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class BioAnalyticsEvent extends Model
{
    protected $fillable = [
        'bio_page_id',
        'event_type',
        'block_id',
        'block_label',
        'block_type',
        'target_url',
        'visitor_hash',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device_type',
        'os',
        'browser',
        'created_at',
        'updated_at',
    ];

    public function bioPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class);
    }

    public static function isBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return false;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|bingpreview|lighthouse|gtmetrix|pingdom|headlesschrome/i',
            $userAgent
        );
    }

    public static function detectReferrerCategory(?string $refererHeader, ?string $utmSource, ?string $userAgent): string
    {
        $source = strtolower(trim(($utmSource ?? '') . ' ' . ($refererHeader ?? '') . ' ' . ($userAgent ?? '')));

        if (str_contains($source, 'instagram') || str_contains($source, 'ig')) {
            return 'Instagram';
        }
        if (str_contains($source, 'whatsapp') || str_contains($source, 'wa.me') || str_contains($source, 'whatsapp')) {
            return 'WhatsApp';
        }
        if (str_contains($source, 'tiktok') || str_contains($source, 'musical_ly') || str_contains($source, 'bytedance')) {
            return 'TikTok';
        }
        if (str_contains($source, 'facebook') || str_contains($source, 'fb.com') || str_contains($source, 'fban') || str_contains($source, 'fbav')) {
            return 'Facebook';
        }
        if (str_contains($source, 'youtube') || str_contains($source, 'youtu.be')) {
            return 'YouTube';
        }
        if (str_contains($source, 'google') || str_contains($source, 'android-app://com.google')) {
            return 'Google';
        }
        if (str_contains($source, 't.co') || str_contains($source, 'twitter') || str_contains($source, 'x.com')) {
            return 'X / Twitter';
        }
        if (str_contains($source, 'telegram') || str_contains($source, 't.me')) {
            return 'Telegram';
        }

        if (! empty($refererHeader)) {
            $host = parse_url($refererHeader, PHP_URL_HOST);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($host && $host !== $appHost && $host !== '127.0.0.1' && $host !== 'localhost') {
                return 'Lainnya';
            }
        }

        return 'Direct';
    }

    public static function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Desktop';
        }

        if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $userAgent)) {
            return 'Tablet';
        }

        if (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone|blackberry|opera mini|iemobile/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    public static function detectOs(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Lainnya';
        }

        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/iphone|ipad|ipod|ios/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/windows nt/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Lainnya';
    }

    public static function detectBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Lainnya';
        }

        if (preg_match('/instagram/i', $userAgent)) {
            return 'Instagram App';
        }
        if (preg_match('/tiktok|musical_ly|bytedance/i', $userAgent)) {
            return 'TikTok App';
        }
        if (preg_match('/fban|fbav/i', $userAgent)) {
            return 'Facebook App';
        }
        if (preg_match('/edg/i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/opr|opera/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/chrome|crios/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/firefox|fxios/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/safari/i', $userAgent)) {
            return 'Safari';
        }

        return 'Lainnya';
    }

    public static function recordFromRequest(
        Request $request,
        BioPage $bioPage,
        string $eventType,
        ?string $blockId = null,
        ?string $blockLabel = null,
        ?string $blockType = null,
        ?string $targetUrl = null,
    ): ?self {
        $userAgent = $request->userAgent();

        if ($request->boolean('preview') || self::isBot($userAgent)) {
            return null;
        }

        $ip = $request->ip() ?? '0.0.0.0';
        $visitorHash = hash('sha256', $ip . '|' . ($userAgent ?? '') . '|' . now()->toDateString());
        $refererHeader = $request->headers->get('referer');
        $utmSource = $request->query('utm_source') ?: session('bio_utm_source');
        $utmMedium = $request->query('utm_medium') ?: session('bio_utm_medium');
        $utmCampaign = $request->query('utm_campaign') ?: session('bio_utm_campaign');

        if ($request->filled('utm_source')) {
            session([
                'bio_utm_source' => $request->query('utm_source'),
                'bio_utm_medium' => $request->query('utm_medium'),
                'bio_utm_campaign' => $request->query('utm_campaign'),
            ]);
        }

        $referrerCategory = self::detectReferrerCategory($refererHeader, $utmSource, $userAgent);
        if ($eventType !== 'view' && $referrerCategory === 'Direct' && session()->has('bio_last_referrer')) {
            $referrerCategory = session('bio_last_referrer', 'Direct');
        } elseif ($eventType === 'view') {
            session(['bio_last_referrer' => $referrerCategory]);
        }

        return self::create([
            'bio_page_id' => $bioPage->id,
            'event_type' => $eventType,
            'block_id' => $blockId,
            'block_label' => $blockLabel,
            'block_type' => $blockType,
            'target_url' => $targetUrl,
            'visitor_hash' => $visitorHash,
            'referrer' => $referrerCategory,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'device_type' => self::detectDeviceType($userAgent),
            'os' => self::detectOs($userAgent),
            'browser' => self::detectBrowser($userAgent),
        ]);
    }
}

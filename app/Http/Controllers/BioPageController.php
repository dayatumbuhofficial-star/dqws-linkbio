<?php

namespace App\Http\Controllers;

use App\Models\BioAnalyticsEvent;
use App\Models\BioPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BioPageController extends Controller
{
    public function show(Request $request, string $slug = 'dqws'): View
    {
        $bioPage = BioPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $theme = array_merge(BioPage::defaultTheme(), $bioPage->published_theme ?? []);
        $allBlocks = BioPage::ensureBlockIds($bioPage->published_blocks ?? []);

        if ($allBlocks !== ($bioPage->published_blocks ?? [])) {
            $bioPage->update(['published_blocks' => $allBlocks]);
        }

        // Filter hanya blok yang masuk jadwal tayang aktif
        $blocks = array_values(array_filter(
            $allBlocks,
            fn (array $block): bool => BioPage::isBlockActive($block)
        ));

        BioAnalyticsEvent::recordFromRequest($request, $bioPage, 'view');

        return view('bio.show', [
            'bioPage' => $bioPage,
            'theme' => $theme,
            'blocks' => $blocks,
        ]);
    }

    public function redirect(Request $request, string $slug, string $blockId): RedirectResponse
    {
        $bioPage = BioPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $theme = array_merge(BioPage::defaultTheme(), $bioPage->published_theme ?? []);

        // 1. Cek jika klik berasal dari Pop-up CTA
        if ($blockId === 'popup-cta') {
            $targetUrl = $theme['popup_cta_url'] ?? '#';
            $label = $theme['popup_cta_label'] ?: ($theme['popup_title'] ?: 'Pop-up Promo');

            BioAnalyticsEvent::recordFromRequest(
                $request,
                $bioPage,
                'popup_click',
                'popup-cta',
                $label,
                'popup',
                $targetUrl
            );

            if (! $targetUrl || $targetUrl === '#') {
                return redirect()->route('bio.show', ['slug' => $slug]);
            }

            return redirect()->away($targetUrl);
        }

        $blocks = BioPage::ensureBlockIds($bioPage->published_blocks ?? []);
        $matchedBlock = null;
        $matchedSlide = null;

        foreach ($blocks as $index => $block) {
            $id = $block['data']['id'] ?? $block['id'] ?? (string) $index;
            if ((string) $id === (string) $blockId || (string) $index === (string) $blockId) {
                $matchedBlock = $block;
                break;
            }

            // Cek di dalam slide carousel / banner_slide
            if (in_array($block['type'] ?? '', ['carousel', 'banner_slide'], true)) {
                $slides = is_array($block['data']['slides'] ?? null)
                    ? $block['data']['slides']
                    : (is_array($block['slides'] ?? null) ? $block['slides'] : []);

                foreach ($slides as $sIdx => $slide) {
                    $sId = $slide['slide_id'] ?? "{$id}-s{$sIdx}";
                    if ((string) $sId === (string) $blockId) {
                        $matchedSlide = $slide;
                        break 2;
                    }
                }
            }
        }

        if ($matchedSlide) {
            $label = ! empty($matchedSlide['title']) ? $matchedSlide['title'] : 'Banner Slide';
            $targetUrl = $matchedSlide['url'] ?? '#';

            BioAnalyticsEvent::recordFromRequest(
                $request,
                $bioPage,
                'click',
                $blockId,
                $label,
                'carousel',
                $targetUrl
            );

            if (! $targetUrl || $targetUrl === '#') {
                return redirect()->route('bio.show', ['slug' => $slug]);
            }

            return redirect()->away($targetUrl);
        }

        if (! $matchedBlock) {
            return redirect()->route('bio.show', ['slug' => $slug]);
        }

        $type = $matchedBlock['type'] ?? 'link';
        $data = $matchedBlock['data'] ?? [];
        $label = $data['label'] ?? $data['title'] ?? ucfirst($type);
        $targetUrl = $this->resolveBlockTargetUrl($type, $data);

        BioAnalyticsEvent::recordFromRequest(
            $request,
            $bioPage,
            'click',
            $blockId,
            $label,
            $type,
            $targetUrl
        );

        if (! $targetUrl || $targetUrl === '#') {
            return redirect()->route('bio.show', ['slug' => $slug]);
        }

        return redirect()->away($targetUrl);
    }

    public function trackEvent(Request $request, string $slug): JsonResponse
    {
        $bioPage = BioPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $eventType = (string) ($request->input('event_type') ?: 'click');

        BioAnalyticsEvent::recordFromRequest(
            $request,
            $bioPage,
            $eventType,
            $request->input('block_id'),
            $request->input('block_label'),
            $request->input('block_type'),
            $request->input('target_url')
        );

        return response()->json(['ok' => true]);
    }

    public function trackClick(Request $request, string $slug): JsonResponse
    {
        return $this->trackEvent($request, $slug);
    }

    protected function resolveBlockTargetUrl(string $type, array $data): string
    {
        if ($type === 'whatsapp') {
            $phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
            $message = $data['message'] ?? '';

            return $phone
                ? 'https://wa.me/' . $phone . ($message !== '' ? '?text=' . rawurlencode($message) : '')
                : '#';
        }

        return $data['url'] ?? '#';
    }
}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bioPage->title }}</title>
    <meta name="description" content="{{ $bioPage->bio }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @php
        $gtmId = trim((string) ($theme['gtm_id'] ?? ''));
        $metaPixelId = trim((string) ($theme['meta_pixel_id'] ?? ''));
        $tiktokPixelId = trim((string) ($theme['tiktok_pixel_id'] ?? ''));
    @endphp

    {{-- Google Tag Manager --}}
    @if ($gtmId !== '')
        <script>
            window.dataLayer = window.dataLayer || [];
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer',@js($gtmId));
        </script>
    @endif

    {{-- Meta (Facebook) Pixel --}}
    @if ($metaPixelId !== '')
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', @js($metaPixelId));
            fbq('track', 'PageView');
        </script>
    @endif

    {{-- TikTok Pixel --}}
    @if ($tiktokPixelId !== '')
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
                ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],
                ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
                for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
                ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},
                ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";
                ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};
                var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;
                var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
                ttq.load(@js($tiktokPixelId));
                ttq.page();
            }(window, document, 'ttq');
        </script>
    @endif

    <script>
        window.sendBioEvent = function(eventType, blockId, label, type, url) {
            try {
                const endpoint = @js(route('bio.track-event', ['slug' => $bioPage->slug]));
                const formData = new FormData();
                formData.append('_token', @js(csrf_token()));
                formData.append('event_type', eventType);
                if (blockId) formData.append('block_id', blockId);
                if (label) formData.append('block_label', label);
                if (type) formData.append('block_type', type);
                if (url) formData.append('target_url', url);

                if (navigator.sendBeacon) {
                    navigator.sendBeacon(endpoint, formData);
                } else {
                    fetch(endpoint, {
                        method: 'POST',
                        body: formData,
                        keepalive: true
                    }).catch(function() {});
                }
            } catch (e) {}
        };

        window.trackBioClick = function(label, type, url) {
            try {
                if (typeof window.fbq === 'function') {
                    window.fbq('track', 'Lead', { content_name: label, content_category: type });
                }
                if (window.ttq && typeof window.ttq.track === 'function') {
                    window.ttq.track('ClickButton', { content_name: label, content_type: type });
                }
                if (Array.isArray(window.dataLayer)) {
                    window.dataLayer.push({
                        event: 'bio_click',
                        block_label: label,
                        block_type: type,
                        target_url: url
                    });
                }
            } catch (e) {}
        };
    </script>
</head>
@php
    $bgColor1 = $theme['bg_color_1'] ?? '#064e3b';
    $bgColor2 = $theme['bg_color_2'] ?? '#0f172a';
    $bgStyle = ($theme['bg_type'] ?? 'gradient') === 'gradient'
        ? "background: linear-gradient(160deg, {$bgColor1} 0%, {$bgColor2} 100%);"
        : "background-color: {$bgColor1};";

    $textColor = $theme['text_color'] ?? '#f8fafc';
    $btnColor = $theme['button_color'] ?? '#10b981';
    $btnTextColor = $theme['button_text_color'] ?? '#ffffff';
    $btnStyleClass = match ($theme['button_style'] ?? 'rounded-xl') {
        'rounded-full' => 'rounded-full',
        'rounded-none' => 'rounded-none',
        default => 'rounded-xl',
    };
    $showSearch = ($theme['show_search'] ?? true) !== false;
    $socialIcons = is_array($theme['social_icons'] ?? null) ? array_values($theme['social_icons']) : [];
    $popupEnabled = ! empty($theme['popup_enabled']);
    $popupImage = $theme['popup_image'] ?? null;
    if (is_array($popupImage)) {
        $popupImage = reset($popupImage) ?: null;
    }

    $searchableCatalog = [];
    foreach ($blocks as $b) {
        $d = $b['data'] ?? [];
        $sTitles = '';
        if (in_array($b['type'] ?? '', ['carousel', 'banner_slide'], true)) {
            $rawSlides = is_array($d['slides'] ?? null)
                ? $d['slides']
                : (is_array($b['slides'] ?? null) ? $b['slides'] : []);
            $sTitles = implode(' ', array_map(fn ($s) => $s['title'] ?? '', $rawSlides));
        }
        $searchableCatalog[] = strtolower(trim(($d['label'] ?? '') . ' ' . ($d['title'] ?? '') . ' ' . ($d['description'] ?? '') . ' ' . $sTitles));
    }
@endphp
<body
    class="min-h-screen antialiased flex flex-col items-center justify-between px-4 py-10 sm:py-14"
    style="{{ $bgStyle }} color: {{ $textColor }};"
>
    @if ($gtmId !== '')
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($gtmId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif

    <main
        x-data="{
            searchQuery: '',
            catalog: @js($searchableCatalog),
            matches(text) {
                const q = (this.searchQuery || '').trim().toLowerCase();
                if (!q) return true;
                return (text || '').toLowerCase().includes(q);
            },
            get hasAnyMatch() {
                const q = (this.searchQuery || '').trim().toLowerCase();
                if (!q) return this.catalog.length > 0;
                return this.catalog.some(t => t.includes(q));
            }
        }"
        class="w-full max-w-md mx-auto"
    >
        {{-- Profil --}}
        <header class="flex flex-col items-center text-center mb-7">
            @if ($bioPage->avatar)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($bioPage->avatar) }}"
                    alt="{{ $bioPage->title }}"
                    class="w-24 h-24 rounded-full object-cover border-2 border-white/30 shadow-lg mb-4"
                >
            @else
                <div
                    class="w-24 h-24 rounded-full flex items-center justify-center text-2xl font-bold border-2 border-white/30 shadow-lg mb-4"
                    style="background-color: {{ $btnColor }}; color: {{ $btnTextColor }};"
                >
                    DQ
                </div>
            @endif

            <h1 class="text-xl sm:text-2xl font-bold tracking-tight leading-snug">
                {{ $bioPage->title }}
            </h1>

            @if ($bioPage->bio)
                <p class="mt-2 text-sm opacity-90 leading-relaxed whitespace-pre-line max-w-sm">
                    {{ $bioPage->bio }}
                </p>
            @endif

            {{-- Baris Ikon Sosial Media (Social Icons Bar) --}}
            @if (count($socialIcons) > 0)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    @foreach ($socialIcons as $soc)
                        @php
                            $platform = $soc['platform'] ?? 'website';
                            $socUrl = $soc['url'] ?? '#';
                        @endphp
                        <a
                            href="{{ $socUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            onclick="window.trackBioClick(@js(ucfirst($platform)), 'social', @js($socUrl))"
                            class="w-10 h-10 rounded-full flex items-center justify-center bg-white/15 hover:bg-white/25 border border-white/25 shadow-sm transition transform hover:scale-110"
                            aria-label="{{ ucfirst($platform) }}"
                        >
                            @if ($platform === 'instagram')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            @elseif ($platform === 'youtube')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            @elseif ($platform === 'tiktok')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                            @elseif ($platform === 'facebook')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                            @elseif ($platform === 'whatsapp')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 2c-5.516 0-9.969 4.453-9.969 9.969 0 1.766.469 3.484 1.344 5l-1.406 5.156 5.281-1.375c1.469.797 3.109 1.219 4.75 1.219 5.516 0 9.969-4.453 9.969-9.969S17.547 2 12.031 2zm5.813 14.125c-.25.703-1.438 1.344-2 1.422-.531.078-1.203.109-1.938-.125-.453-.141-1.031-.328-1.766-.656-3.094-1.344-5.109-4.469-5.266-4.672-.156-.203-1.266-1.688-1.266-3.219s.797-2.281 1.078-2.594c.281-.313.609-.391.813-.391s.406 0 .578.016c.188.016.438-.078.688.531.25.609.859 2.094.938 2.25.078.156.125.344.031.547-.094.203-.156.328-.313.5-.156.172-.328.391-.469.531-.156.156-.313.328-.141.641.172.313.781 1.297 1.672 2.094 1.156 1.031 2.125 1.344 2.438 1.5.313.156.484.125.672-.078.188-.203.781-.906.984-1.219.203-.313.406-.266.688-.156.281.109 1.781.844 2.094 1 .313.156.516.234.594.359.078.125.078.734-.172 1.438z"/></svg>
                            @elseif ($platform === 'telegram')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            @elseif ($platform === 'maps')
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            @else
                                <svg width="18" height="18" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        {{-- Kolom Pencarian Instan (Search Bar) --}}
        @if ($showSearch)
            <div class="mb-5 relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Cari tautan, program, atau informasi..."
                    class="w-full pl-11 pr-10 py-3 text-sm bg-white/15 backdrop-blur-md border border-white/25 placeholder-white/65 text-white focus:outline-none focus:border-white/70 shadow-sm transition {{ $btnStyleClass }}"
                >
                <svg width="18" height="18" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-75 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <button
                    type="button"
                    x-show="searchQuery !== ''"
                    x-cloak
                    @click="searchQuery = ''"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-xs transition"
                    aria-label="Bersihkan pencarian"
                >
                    &#10005;
                </button>
            </div>
        @endif

        {{-- Daftar Blok Konten --}}
        <section class="space-y-3.5">
            @foreach ($blocks as $index => $block)
                @php
                    $type = $block['type'] ?? '';
                    $data = $block['data'] ?? [];
                    $blockId = $data['id'] ?? $block['id'] ?? (string) $index;
                    $trackRedirectUrl = route('bio.redirect', ['slug' => $bioPage->slug, 'blockId' => $blockId]);
                    $searchText = $searchableCatalog[$index] ?? '';
                @endphp

                <div x-show="matches(@js($searchText))" x-transition.opacity>
                    @if ($type === 'section')
                        <div class="pt-4 pb-1 text-center">
                            <h2 class="text-xs font-semibold uppercase tracking-widest opacity-75">
                                {{ $data['title'] ?? '' }}
                            </h2>
                        </div>

                    @elseif ($type === 'link')
                        @php
                            $isHighlight = !empty($data['highlight']);
                            $label = $data['label'] ?? 'Tautan';
                        @endphp
                        <a
                            href="{{ $trackRedirectUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            onclick="window.trackBioClick(@js($label), 'link', @js($data['url'] ?? ''))"
                            class="block w-full px-5 py-3.5 text-center text-sm sm:text-base font-semibold transition-transform duration-150 hover:scale-[1.02] active:scale-[0.99] shadow-md {{ $btnStyleClass }} {{ $isHighlight ? 'ring-2 ring-white/80 shadow-xl' : '' }}"
                            style="background-color: {{ $btnColor }}; color: {{ $btnTextColor }};"
                        >
                            {{ $label }}
                        </a>

                    @elseif ($type === 'whatsapp')
                        @php
                            $label = $data['label'] ?? 'Chat WhatsApp';
                        @endphp
                        <a
                            href="{{ $trackRedirectUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            onclick="window.trackBioClick(@js($label), 'whatsapp', 'whatsapp')"
                            class="flex items-center justify-center gap-2.5 w-full px-5 py-3.5 text-center text-sm sm:text-base font-semibold transition-transform duration-150 hover:scale-[1.02] active:scale-[0.99] shadow-md {{ $btnStyleClass }}"
                            style="background-color: {{ $btnColor }}; color: {{ $btnTextColor }};"
                        >
                            <svg width="20" height="20" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12.031 2c-5.516 0-9.969 4.453-9.969 9.969 0 1.766.469 3.484 1.344 5l-1.406 5.156 5.281-1.375c1.469.797 3.109 1.219 4.75 1.219 5.516 0 9.969-4.453 9.969-9.969S17.547 2 12.031 2zm5.813 14.125c-.25.703-1.438 1.344-2 1.422-.531.078-1.203.109-1.938-.125-.453-.141-1.031-.328-1.766-.656-3.094-1.344-5.109-4.469-5.266-4.672-.156-.203-1.266-1.688-1.266-3.219s.797-2.281 1.078-2.594c.281-.313.609-.391.813-.391s.406 0 .578.016c.188.016.438-.078.688.531.25.609.859 2.094.938 2.25.078.156.125.344.031.547-.094.203-.156.328-.313.5-.156.172-.328.391-.469.531-.156.156-.313.328-.141.641.172.313.781 1.297 1.672 2.094 1.156 1.031 2.125 1.344 2.438 1.5.313.156.484.125.672-.078.188-.203.781-.906.984-1.219.203-.313.406-.266.688-.156.281.109 1.781.844 2.094 1 .313.156.516.234.594.359.078.125.078.734-.172 1.438z"/>
                            </svg>
                            <span>{{ $label }}</span>
                        </a>

                    @elseif ($type === 'banner')
                        @php
                            $bannerUrl = $data['url'] ?? null;
                            $bannerTitle = $data['title'] ?? 'Program';
                        @endphp
                        @if ($bannerUrl && $bannerUrl !== '#')
                            <a
                                href="{{ $trackRedirectUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                onclick="window.trackBioClick(@js($bannerTitle), 'banner', @js($bannerUrl))"
                                class="block w-full p-4 sm:p-5 text-left backdrop-blur-md bg-white/15 border border-white/25 transition hover:bg-white/20 shadow-md {{ $btnStyleClass }}"
                            >
                                <div class="text-[11px] font-bold uppercase tracking-wider opacity-80 mb-1">
                                    Program / Event
                                </div>
                                <div class="text-base font-bold leading-snug">
                                    {{ $bannerTitle }}
                                </div>
                                @if (!empty($data['description']))
                                    <p class="mt-1.5 text-xs sm:text-sm opacity-90 leading-relaxed">
                                        {{ $data['description'] }}
                                    </p>
                                @endif
                            </a>
                        @else
                            <div class="w-full p-4 sm:p-5 text-left backdrop-blur-md bg-white/15 border border-white/25 shadow-md {{ $btnStyleClass }}">
                                <div class="text-[11px] font-bold uppercase tracking-wider opacity-80 mb-1">
                                    Program / Event
                                </div>
                                <div class="text-base font-bold leading-snug">
                                    {{ $bannerTitle }}
                                </div>
                                @if (!empty($data['description']))
                                    <p class="mt-1.5 text-xs sm:text-sm opacity-90 leading-relaxed">
                                        {{ $data['description'] }}
                                    </p>
                                @endif
                            </div>
                        @endif

                    @elseif ($type === 'carousel' || $type === 'banner_slide')
                        @php
                            $slides = is_array($data['slides'] ?? null)
                                ? array_values($data['slides'])
                                : (is_array($block['slides'] ?? null) ? array_values($block['slides']) : []);
                            $totalSlides = count($slides);
                        @endphp
                        @if ($totalSlides > 0)
                            <div
                                x-data="{
                                    active: 0,
                                    total: {{ $totalSlides }},
                                    timer: null,
                                    init() {
                                        if (this.total > 1) {
                                            this.startAuto();
                                        }
                                    },
                                    startAuto() {
                                        this.timer = setInterval(() => {
                                            this.active = (this.active + 1) % this.total;
                                        }, 4000);
                                    },
                                    stopAuto() {
                                        if (this.timer) clearInterval(this.timer);
                                    },
                                    prev() {
                                        this.stopAuto();
                                        this.active = (this.active - 1 + this.total) % this.total;
                                        this.startAuto();
                                    },
                                    next() {
                                        this.stopAuto();
                                        this.active = (this.active + 1) % this.total;
                                        this.startAuto();
                                    }
                                }"
                                class="relative w-full overflow-hidden shadow-lg border border-white/20 bg-black/20 {{ $btnStyleClass }}"
                                style="aspect-ratio: 650 / 350; min-height: 180px;"
                            >
                                @foreach ($slides as $sIdx => $slide)
                                    @php
                                        $slideId = $slide['slide_id'] ?? "{$blockId}-s{$sIdx}";
                                        $slideImg = \App\Models\BioPage::unwrapFilePath($slide['image'] ?? null);
                                        $slideImgUrl = null;
                                        if ($slideImg) {
                                            if (str_starts_with($slideImg, 'http://') || str_starts_with($slideImg, 'https://')) {
                                                $slideImgUrl = $slideImg;
                                            } else {
                                                $slideImgUrl = asset('storage/' . ltrim($slideImg, '/'));
                                            }
                                        }
                                        $slideTitle = $slide['title'] ?? '';
                                        $slideTarget = $slide['url'] ?? null;
                                        $slideRedirectUrl = ($slideTarget && $slideTarget !== '#')
                                            ? route('bio.redirect', ['slug' => $bioPage->slug, 'blockId' => $slideId])
                                            : null;
                                    @endphp
                                    <div
                                        x-show="active === {{ $sIdx }}"
                                        x-transition.opacity.duration.300ms
                                        class="absolute inset-0 w-full h-full"
                                    >
                                        @if ($slideRedirectUrl)
                                            <a
                                                href="{{ $slideRedirectUrl }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                onclick="window.trackBioClick(@js($slideTitle ?: 'Banner Slide'), 'carousel', @js($slideTarget))"
                                                class="block w-full h-full relative"
                                            >
                                                @if ($slideImgUrl)
                                                    <img src="{{ $slideImgUrl }}" alt="{{ $slideTitle ?: 'Slide' }}" class="w-full h-full object-cover" loading="eager">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center p-4 bg-white/10 text-center">
                                                        <span class="text-sm font-bold">{{ $slideTitle ?: 'Banner Slide DQWS' }}</span>
                                                    </div>
                                                @endif
                                                @if ($slideTitle !== '')
                                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 text-left">
                                                        <p class="text-xs sm:text-sm font-semibold text-white truncate">{{ $slideTitle }}</p>
                                                    </div>
                                                @endif
                                            </a>
                                        @else
                                            <div class="w-full h-full relative">
                                                @if ($slideImgUrl)
                                                    <img src="{{ $slideImgUrl }}" alt="{{ $slideTitle ?: 'Slide' }}" class="w-full h-full object-cover" loading="eager">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center p-4 bg-white/10 text-center">
                                                        <span class="text-sm font-bold">{{ $slideTitle ?: 'Banner Slide DQWS' }}</span>
                                                    </div>
                                                @endif
                                                @if ($slideTitle !== '')
                                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 text-left">
                                                        <p class="text-xs sm:text-sm font-semibold text-white truncate">{{ $slideTitle }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                @if ($totalSlides > 1)
                                    <button
                                        type="button"
                                        @click="prev()"
                                        class="absolute left-2.5 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white flex items-center justify-center transition"
                                        aria-label="Slide Sebelumnya"
                                    >
                                        &#10094;
                                    </button>
                                    <button
                                        type="button"
                                        @click="next()"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white flex items-center justify-center transition"
                                        aria-label="Slide Berikutnya"
                                    >
                                        &#10095;
                                    </button>

                                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 z-10 flex items-center gap-1.5">
                                        @foreach ($slides as $sIdx => $slide)
                                            <button
                                                type="button"
                                                @click="active = {{ $sIdx }}"
                                                class="h-1.5 rounded-full transition-all"
                                                :class="active === {{ $sIdx }} ? 'w-5 bg-white' : 'w-1.5 bg-white/50'"
                                                aria-label="Pilih Slide {{ $sIdx + 1 }}"
                                            ></button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach

            {{-- Pesan Jika Pencarian Tidak Ditemukan --}}
            <div
                x-show="searchQuery.trim() !== '' && !hasAnyMatch"
                x-cloak
                class="py-8 px-4 text-center rounded-2xl bg-white/10 border border-white/15 text-sm opacity-85"
            >
                Tidak ada tautan yang cocok dengan pencarian Anda.
            </div>
        </section>
    </main>

    <footer class="mt-12 pt-6 text-center text-xs opacity-65">
        {{ $theme['footer_text'] ?? $bioPage->footer_text ?? '© ' . date('Y') . ' DQWS Link-in-Bio' }}
    </footer>

    {{-- Modal Pop-up Pengumuman / Promo --}}
    @if ($popupEnabled)
        @php
            $popupDelayMs = max(0, (int) ($theme['popup_delay'] ?? 1)) * 1000;
            $popupFreq = $theme['popup_frequency'] ?? 'always';
            $popupTitle = $theme['popup_title'] ?? '';
            $popupMessage = $theme['popup_message'] ?? '';
            $popupCtaLabel = $theme['popup_cta_label'] ?? 'Lihat Selengkapnya';
            $popupCtaUrl = $theme['popup_cta_url'] ?? '';
            $popupImgUrl = $popupImage
                ? (str_starts_with($popupImage, 'http') ? $popupImage : \Illuminate\Support\Facades\Storage::disk('public')->url($popupImage))
                : null;
            $popupRedirectUrl = route('bio.redirect', ['slug' => $bioPage->slug, 'blockId' => 'popup-cta']);
        @endphp
        <div
            x-data="{
                show: false,
                closed: false,
                viewSent: false,
                freq: @js($popupFreq),
                delay: {{ $popupDelayMs }},
                storageKey: 'dqws_popup_seen_{{ $bioPage->slug }}_' + new Date().toISOString().slice(0, 10),
                init() {
                    if (this.freq === 'daily') {
                        try {
                            if (localStorage.getItem(this.storageKey)) return;
                        } catch (e) {}
                    }
                    setTimeout(() => {
                        this.show = true;
                        if (!this.viewSent) {
                            this.viewSent = true;
                            window.sendBioEvent('popup_view', 'popup', @js($popupTitle ?: 'Pop-up Promo'), 'popup', @js($popupCtaUrl ?: ''));
                        }
                        if (this.freq === 'daily') {
                            try {
                                localStorage.setItem(this.storageKey, '1');
                            } catch (e) {}
                        }
                    }, this.delay);
                },
                close() {
                    if (!this.closed && this.show) {
                        this.closed = true;
                        this.show = false;
                        window.sendBioEvent('popup_close', 'popup', @js($popupTitle ?: 'Pop-up Promo'), 'popup', @js($popupCtaUrl ?: ''));
                    }
                }
            }"
            @keydown.escape.window="close()"
            x-show="show"
            x-cloak
            x-transition.opacity.duration.250ms
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div
                @click.outside="close()"
                class="w-full max-w-sm rounded-2xl bg-gray-900 border border-white/15 text-white p-5 shadow-2xl relative text-center"
            >
                <button
                    type="button"
                    @click="close()"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-sm text-white transition"
                    aria-label="Tutup Pop-up"
                >
                    &#10005;
                </button>

                @if ($popupImgUrl)
                    <img
                        src="{{ $popupImgUrl }}"
                        alt="{{ $popupTitle ?: 'Promo' }}"
                        class="w-full h-44 object-cover rounded-xl mb-4"
                    >
                @endif

                @if ($popupTitle !== '')
                    <h3 class="text-lg font-bold leading-snug">{{ $popupTitle }}</h3>
                @endif

                @if ($popupMessage !== '')
                    <p class="mt-2 text-sm text-gray-300 leading-relaxed whitespace-pre-line">{{ $popupMessage }}</p>
                @endif

                @if ($popupCtaUrl !== '')
                    <a
                        href="{{ $popupRedirectUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        onclick="window.trackBioClick(@js($popupCtaLabel ?: $popupTitle ?: 'Pop-up Promo'), 'popup', @js($popupCtaUrl))"
                        class="mt-5 block w-full py-3 px-5 font-bold text-sm shadow-lg transition transform hover:scale-[1.02] {{ $btnStyleClass }}"
                        style="background-color: {{ $btnColor }}; color: {{ $btnTextColor }};"
                    >
                        {{ $popupCtaLabel }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</body>
</html>

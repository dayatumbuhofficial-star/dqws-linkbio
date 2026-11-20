<x-filament-panels::page>
    @vite('resources/css/app.css')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Kolom Kiri: Editor Form (7 Kolom) --}}
        <div class="lg:col-span-7">
            <form wire:submit="saveDraft">
                {{ $this->form }}
            </form>
        </div>

        {{-- Kolom Kanan: Live Preview Mockup Layar HP (5 Kolom, Sticky) --}}
        <div class="lg:col-span-5 lg:sticky lg:top-20 flex flex-col items-center">
            <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Live Preview Mobile
            </div>

            <div
                x-data="{
                    state: $wire.entangle('data'),
                    showPopupPreview: false,
                    previewSearch: '',
                    get theme() {
                        return this.state?.theme || {};
                    },
                    get blocks() {
                        return Object.values(this.state?.blocks || {});
                    },
                    get socialIcons() {
                        return Object.values(this.theme?.social_icons || {});
                    },
                    get bgStyle() {
                        const c1 = this.theme.bg_color_1 || '#064e3b';
                        const c2 = this.theme.bg_color_2 || '#0f172a';
                        if (this.theme.bg_type === 'gradient') {
                            return `background: linear-gradient(160deg, ${c1} 0%, ${c2} 100%);`;
                        }
                        return `background-color: ${c1};`;
                    },
                    get btnRadius() {
                        const style = this.theme.button_style || 'rounded-xl';
                        if (style === 'rounded-full') return '9999px';
                        if (style === 'rounded-none') return '0px';
                        return '0.75rem';
                    },
                    waHref(data) {
                        const phone = (data?.phone || '').replace(/[^0-9]/g, '');
                        const msg = encodeURIComponent(data?.message || '');
                        return phone ? `https://wa.me/${phone}${msg ? '?text=' + msg : ''}` : '#';
                    },
                    isScheduled(data) {
                        return Boolean(data?.start_at || data?.end_at);
                    },
                    slideList(data) {
                        return Object.values(data?.slides || {});
                    },
                    matchesBlock(block) {
                        const q = (this.previewSearch || '').trim().toLowerCase();
                        if (!q) return true;
                        const d = block?.data || {};
                        const slideTitles = Object.values(d.slides || {}).map(s => s?.title || '').join(' ');
                        const hay = `${d.label || ''} ${d.title || ''} ${d.description || ''} ${slideTitles}`.toLowerCase();
                        return hay.includes(q);
                    },
                    get hasMatchingBlocks() {
                        return this.blocks.some(b => this.matchesBlock(b));
                    },
                    resolveStorageUrl(val) {
                        if (!val) return null;
                        if (typeof val === 'object') {
                            const first = Object.values(val)[0];
                            if (typeof first === 'string') val = first;
                            else return null;
                        }
                        if (typeof val !== 'string' || val === '') return null;
                        if (val.startsWith('http://') || val.startsWith('https://') || val.startsWith('/')) return val;
                        return '/storage/' + val;
                    }
                }"
                class="w-full max-w-[360px] rounded-[2.75rem] border-[10px] border-gray-900 dark:border-gray-800 bg-gray-900 shadow-2xl overflow-hidden relative"
            >
                {{-- Notch HP --}}
                <div class="bg-gray-900 pt-2 pb-2 flex justify-center">
                    <div class="h-4 w-24 rounded-full bg-gray-800"></div>
                </div>

                {{-- Layar HP --}}
                <div
                    :style="`${bgStyle} color: ${theme.text_color || '#f8fafc'};`"
                    class="min-h-[580px] max-h-[72vh] overflow-y-auto px-5 py-6 transition-all duration-200 relative"
                >
                    {{-- Indikator Pop-up Aktif di Preview --}}
                    <template x-if="theme.popup_enabled">
                        <div class="mb-4 flex justify-center">
                            <button
                                type="button"
                                @click="showPopupPreview = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-amber-500 text-gray-950 shadow hover:bg-amber-400 transition"
                            >
                                <span>Pop-up Promo Aktif — Klik Preview</span>
                            </button>
                        </div>
                    </template>

                    {{-- Profil --}}
                    <div class="flex flex-col items-center text-center mb-5">
                        @if ($this->avatarUrl)
                            <img
                                src="{{ $this->avatarUrl }}"
                                alt="Avatar"
                                class="h-20 w-20 rounded-full object-cover border-2 border-white/30 shadow-md mb-3"
                            />
                        @else
                            <div
                                class="h-20 w-20 rounded-full flex items-center justify-center text-xl font-bold border-2 border-white/30 shadow-md mb-3"
                                :style="`background-color: ${theme.button_color || '#10b981'}; color: ${theme.button_text_color || '#ffffff'};`"
                            >
                                DQ
                            </div>
                        @endif

                        <h2
                            class="text-base font-bold leading-snug"
                            x-text="state?.title || 'Judul Halaman'"
                        ></h2>

                        <p
                            x-show="state?.bio"
                            class="mt-1.5 text-xs opacity-85 leading-relaxed whitespace-pre-line"
                            x-text="state?.bio"
                        ></p>
                    </div>

                    {{-- Kolom Pencarian (Search Bar Preview) --}}
                    <template x-if="theme.show_search !== false">
                        <div class="mb-4 relative">
                            <input
                                type="text"
                                x-model="previewSearch"
                                placeholder="Cari tautan atau program..."
                                class="w-full pl-9 pr-8 py-2.5 text-xs bg-white/15 backdrop-blur-md border border-white/25 placeholder-white/60 text-white focus:outline-none focus:border-white/60 transition"
                                :style="`border-radius: ${btnRadius};`"
                            />
                            <svg width="15" height="15" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 opacity-70 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <button
                                type="button"
                                x-show="previewSearch !== ''"
                                @click="previewSearch = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs opacity-75 hover:opacity-100"
                            >
                                &#10005;
                            </button>
                        </div>
                    </template>

                    {{-- Daftar Blok --}}
                    <div class="space-y-3">
                        <template x-for="(block, index) in blocks" :key="index">
                            <div class="relative" x-show="matchesBlock(block)">
                                {{-- Badge Terjadwal --}}
                                <template x-if="isScheduled(block.data)">
                                    <span class="absolute -top-2 right-2 z-10 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-amber-500 text-gray-950 shadow">
                                        Terjadwal
                                    </span>
                                </template>

                                {{-- Judul Seksi / Pemisah --}}
                                <template x-if="block.type === 'section'">
                                    <div class="pt-3 pb-1 text-center">
                                        <span
                                            class="text-[11px] font-semibold uppercase tracking-widest opacity-75"
                                            x-text="block.data?.title || 'Seksi'"
                                        ></span>
                                    </div>
                                </template>

                                {{-- Tombol Link --}}
                                <template x-if="block.type === 'link'">
                                    <a
                                        :href="block.data?.url || '#'"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="block w-full px-4 py-3 text-center text-sm font-semibold transition transform hover:scale-[1.01] shadow-sm"
                                        :class="block.data?.highlight ? 'ring-2 ring-white/70 shadow-lg' : ''"
                                        :style="`background-color: ${theme.button_color || '#10b981'}; color: ${theme.button_text_color || '#ffffff'}; border-radius: ${btnRadius};`"
                                    >
                                        <span x-text="block.data?.label || 'Tombol Link'"></span>
                                    </a>
                                </template>

                                {{-- Tombol WhatsApp --}}
                                <template x-if="block.type === 'whatsapp'">
                                    <a
                                        :href="waHref(block.data)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center justify-center gap-2 w-full px-4 py-3 text-center text-sm font-semibold transition transform hover:scale-[1.01] shadow-sm"
                                        :style="`background-color: ${theme.button_color || '#10b981'}; color: ${theme.button_text_color || '#ffffff'}; border-radius: ${btnRadius};`"
                                    >
                                        <svg width="20" height="20" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12.031 2c-5.516 0-9.969 4.453-9.969 9.969 0 1.766.469 3.484 1.344 5l-1.406 5.156 5.281-1.375c1.469.797 3.109 1.219 4.75 1.219 5.516 0 9.969-4.453 9.969-9.969S17.547 2 12.031 2zm5.813 14.125c-.25.703-1.438 1.344-2 1.422-.531.078-1.203.109-1.938-.125-.453-.141-1.031-.328-1.766-.656-3.094-1.344-5.109-4.469-5.266-4.672-.156-.203-1.266-1.688-1.266-3.219s.797-2.281 1.078-2.594c.281-.313.609-.391.813-.391s.406 0 .578.016c.188.016.438-.078.688.531.25.609.859 2.094.938 2.25.078.156.125.344.031.547-.094.203-.156.328-.313.5-.156.172-.328.391-.469.531-.156.156-.313.328-.141.641.172.313.781 1.297 1.672 2.094 1.156 1.031 2.125 1.344 2.438 1.5.313.156.484.125.672-.078.188-.203.781-.906.984-1.219.203-.313.406-.266.688-.156.281.109 1.781.844 2.094 1 .313.156.516.234.594.359.078.125.078.734-.172 1.438z"/>
                                        </svg>
                                        <span x-text="block.data?.label || 'Chat WhatsApp'"></span>
                                    </a>
                                </template>

                                {{-- Banner Program/Event --}}
                                <template x-if="block.type === 'banner'">
                                    <a
                                        :href="block.data?.url || '#'"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="block w-full p-4 text-left backdrop-blur-md bg-white/15 border border-white/25 transition hover:bg-white/20 shadow-sm"
                                        :style="`border-radius: ${btnRadius};`"
                                    >
                                        <div class="text-xs font-bold uppercase tracking-wide opacity-80 mb-1">Program / Event</div>
                                        <div class="text-sm font-bold leading-snug" x-text="block.data?.title || 'Judul Program'"></div>
                                        <p
                                            x-show="block.data?.description"
                                            class="mt-1 text-xs opacity-85 leading-relaxed"
                                            x-text="block.data?.description"
                                        ></p>
                                    </a>
                                </template>

                                {{-- Banner Slide / Carousel (650x350) --}}
                                <template x-if="block.type === 'carousel' || block.type === 'banner_slide'">
                                    <div
                                        x-data="{ activeSlide: 0 }"
                                        class="relative w-full overflow-hidden shadow-md border border-white/20 bg-black/20"
                                        :style="`border-radius: ${btnRadius}; aspect-ratio: 650 / 350;`"
                                    >
                                        <template x-for="(slide, sIdx) in slideList(block.data)" :key="sIdx">
                                            <a
                                                x-show="activeSlide === sIdx"
                                                :href="slide.url || '#'"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="block w-full h-full relative"
                                            >
                                                <template x-if="resolveStorageUrl(slide.image)">
                                                    <img
                                                        :src="resolveStorageUrl(slide.image)"
                                                        :alt="slide.title || 'Slide'"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </template>
                                                <template x-if="!resolveStorageUrl(slide.image)">
                                                    <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center bg-white/10">
                                                        <span class="text-xs font-bold" x-text="slide.title || 'Banner Slide 650x350'"></span>
                                                        <span class="text-[10px] opacity-70 mt-1">Simpan draft untuk preview gambar baru</span>
                                                    </div>
                                                </template>
                                                <template x-if="slide.title">
                                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-2.5 text-left">
                                                        <p class="text-xs font-semibold text-white truncate" x-text="slide.title"></p>
                                                    </div>
                                                </template>
                                            </a>
                                        </template>

                                        <template x-if="slideList(block.data).length > 1">
                                            <div>
                                                <button
                                                    type="button"
                                                    @click.prevent="activeSlide = (activeSlide - 1 + slideList(block.data).length) % slideList(block.data).length"
                                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center text-xs hover:bg-black/70"
                                                >
                                                    &#10094;
                                                </button>
                                                <button
                                                    type="button"
                                                    @click.prevent="activeSlide = (activeSlide + 1) % slideList(block.data).length"
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center text-xs hover:bg-black/70"
                                                >
                                                    &#10095;
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Pesan Jika Pencarian Kosong --}}
                        <div
                            x-show="previewSearch.trim() !== '' && !hasMatchingBlocks"
                            class="py-6 text-center text-xs opacity-75"
                        >
                            Tidak ada tautan yang cocok.
                        </div>
                    </div>

                    {{-- Baris Ikon Sosial Media di Bagian Bawah (Di Bawah Blok & Di Atas Footer) --}}
                    <template x-if="socialIcons.length > 0">
                        <div class="mt-6 pt-2 flex flex-wrap items-center justify-center gap-2.5">
                            <template x-for="(soc, sIdx) in socialIcons" :key="sIdx">
                                <a
                                    :href="soc.url || '#'"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-9 h-9 rounded-full flex items-center justify-center bg-white/15 hover:bg-white/25 border border-white/20 transition transform hover:scale-105"
                                    :title="soc.platform"
                                >
                                    <template x-if="soc.platform === 'instagram'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'youtube'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'tiktok'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'facebook'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'whatsapp'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 2c-5.516 0-9.969 4.453-9.969 9.969 0 1.766.469 3.484 1.344 5l-1.406 5.156 5.281-1.375c1.469.797 3.109 1.219 4.75 1.219 5.516 0 9.969-4.453 9.969-9.969S17.547 2 12.031 2zm5.813 14.125c-.25.703-1.438 1.344-2 1.422-.531.078-1.203.109-1.938-.125-.453-.141-1.031-.328-1.766-.656-3.094-1.344-5.109-4.469-5.266-4.672-.156-.203-1.266-1.688-1.266-3.219s.797-2.281 1.078-2.594c.281-.313.609-.391.813-.391s.406 0 .578.016c.188.016.438-.078.688.531.25.609.859 2.094.938 2.25.078.156.125.344.031.547-.094.203-.156.328-.313.5-.156.172-.328.391-.469.531-.156.156-.313.328-.141.641.172.313.781 1.297 1.672 2.094 1.156 1.031 2.125 1.344 2.438 1.5.313.156.484.125.672-.078.188-.203.781-.906.984-1.219.203-.313.406-.266.688-.156.281.109 1.781.844 2.094 1 .313.156.516.234.594.359.078.125.078.734-.172 1.438z"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'telegram'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'website'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                                    </template>
                                    <template x-if="soc.platform === 'maps'">
                                        <svg width="18" height="18" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </template>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- Footer Preview --}}
                    <div
                        class="mt-6 pt-3 text-center text-[10px] opacity-60"
                        x-text="state?.footer_text || theme?.footer_text || '© 2026 DQWS Link-in-Bio'"
                    >
                    </div>

                    {{-- Modal Pop-up Preview di dalam Layar HP --}}
                    <div
                        x-show="showPopupPreview"
                        x-cloak
                        class="absolute inset-0 z-30 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
                    >
                        <div class="w-full rounded-2xl bg-gray-900 border border-white/15 text-white p-4 shadow-2xl relative text-center">
                            <button
                                type="button"
                                @click="showPopupPreview = false"
                                class="absolute top-2.5 right-2.5 w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-xs"
                            >
                                &#10005;
                            </button>

                            @if ($this->popupImageUrl)
                                <img
                                    src="{{ $this->popupImageUrl }}"
                                    alt="Pop-up"
                                    class="w-full h-32 object-cover rounded-xl mb-3"
                                />
                            @endif

                            <h4 class="text-sm font-bold" x-text="theme.popup_title || 'Judul Pop-up'"></h4>
                            <p
                                x-show="theme.popup_message"
                                class="mt-1.5 text-xs text-gray-300 leading-relaxed whitespace-pre-line"
                                x-text="theme.popup_message"
                            ></p>

                            <template x-if="theme.popup_cta_label">
                                <a
                                    :href="theme.popup_cta_url || '#'"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-3.5 block w-full py-2.5 px-4 rounded-xl text-xs font-bold shadow transition"
                                    :style="`background-color: ${theme.button_color || '#10b981'}; color: ${theme.button_text_color || '#ffffff'};`"
                                    x-text="theme.popup_cta_label"
                                ></a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

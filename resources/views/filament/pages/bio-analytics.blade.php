<x-filament-panels::page>
    @vite('resources/css/app.css')

    @php
        $stats = $this->stats;
        $daily = $stats['daily_trend'];
        $maxDaily = max(1, $stats['max_daily_value']);
        $countDays = count($daily);

        $svgW = 720;
        $svgH = 220;
        $padX = 32;
        $padY = 24;
        $plotW = $svgW - ($padX * 2);
        $plotH = $svgH - ($padY * 2);

        $viewPoints = [];
        $clickPoints = [];
        foreach ($daily as $idx => $item) {
            $x = $countDays > 1
                ? round($padX + ($idx / ($countDays - 1)) * $plotW, 1)
                : round($svgW / 2, 1);
            $yView = round($padY + $plotH - (($item['views'] / $maxDaily) * $plotH), 1);
            $yClick = round($padY + $plotH - (($item['clicks'] / $maxDaily) * $plotH), 1);

            $viewPoints[] = "{$x},{$yView}";
            $clickPoints[] = "{$x},{$yClick}";
        }

        $viewPolyline = implode(' ', $viewPoints);
        $clickPolyline = implode(' ', $clickPoints);
        $firstX = $countDays > 1 ? $padX : round($svgW / 2, 1);
        $lastX = $countDays > 1 ? ($padX + $plotW) : round($svgW / 2, 1);
        $baseY = $padY + $plotH;
        $viewArea = $viewPolyline ? "{$firstX},{$baseY} {$viewPolyline} {$lastX},{$baseY}" : '';
        $clickArea = $clickPolyline ? "{$firstX},{$baseY} {$clickPolyline} {$lastX},{$baseY}" : '';

        $sourceColors = [
            'Instagram' => '#e1306c',
            'WhatsApp' => '#10b981',
            'TikTok' => '#06b6d4',
            'Facebook' => '#3b82f6',
            'YouTube' => '#ef4444',
            'Google' => '#f59e0b',
            'Direct' => '#6366f1',
            'Lainnya' => '#94a3b8',
        ];
    @endphp

    <div wire:poll.10s class="space-y-6 text-white">
        {{-- Top Bar: Status Realtime & Compact Date Range Picker Popover --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Realtime Analytics Aktif (Auto-Update 10s)</span>
                </span>
            </div>

            {{-- Compact Date Range Picker Popover --}}
            <div
                x-data="{
                    open: false,
                    activePreset: @js($stats['preset'] ?? '30d'),
                    tempStart: @js($stats['start_date']),
                    tempEnd: @js($stats['end_date']),
                    selectingEnd: false,
                    viewMonth: 0,
                    viewYear: 2026,
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    presets: [
                        { id: 'today', label: 'Hari Ini' },
                        { id: '7d', label: '7 Hari Terakhir' },
                        { id: 'last_week', label: 'Minggu Lalu' },
                        { id: 'this_month', label: 'Bulan Ini' },
                        { id: 'last_month', label: 'Bulan Lalu' },
                        { id: '3m', label: '3 Bulan Terakhir' },
                        { id: '6m', label: '6 Bulan Terakhir' },
                        { id: 'this_year', label: 'Tahun Ini' }
                    ],
                    init() {
                        const d = this.parseIso(this.tempEnd);
                        this.viewMonth = d.getMonth();
                        this.viewYear = d.getFullYear();
                    },
                    parseIso(str) {
                        const parts = (str || '').split('-');
                        if (parts.length === 3) {
                            return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                        }
                        return new Date();
                    },
                    toIso(date) {
                        const y = date.getFullYear();
                        const m = String(date.getMonth() + 1).padStart(2, '0');
                        const d = String(date.getDate()).padStart(2, '0');
                        return `${y}-${m}-${d}`;
                    },
                    formatId(isoStr) {
                        if (!isoStr) return '-';
                        const d = this.parseIso(isoStr);
                        return `${String(d.getDate()).padStart(2, '0')} ${this.shortMonths[d.getMonth()]} ${d.getFullYear()}`;
                    },
                    prevMonth() {
                        if (this.viewMonth === 0) {
                            this.viewMonth = 11;
                            this.viewYear--;
                        } else {
                            this.viewMonth--;
                        }
                    },
                    nextMonth() {
                        if (this.viewMonth === 11) {
                            this.viewMonth = 0;
                            this.viewYear++;
                        } else {
                            this.viewMonth++;
                        }
                    },
                    selectPreset(id) {
                        this.activePreset = id;
                        this.selectingEnd = false;
                        const now = new Date();
                        let start = new Date(now);
                        let end = new Date(now);

                        if (id === 'today') {
                            // start & end = today
                        } else if (id === '7d') {
                            start.setDate(now.getDate() - 6);
                        } else if (id === 'last_week') {
                            const day = now.getDay() || 7;
                            end.setDate(now.getDate() - day);
                            start = new Date(end);
                            start.setDate(end.getDate() - 6);
                        } else if (id === 'this_month') {
                            start = new Date(now.getFullYear(), now.getMonth(), 1);
                            end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                        } else if (id === 'last_month') {
                            start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                            end = new Date(now.getFullYear(), now.getMonth(), 0);
                        } else if (id === '3m') {
                            start = new Date(now.getFullYear(), now.getMonth() - 2, 1);
                        } else if (id === '6m') {
                            start = new Date(now.getFullYear(), now.getMonth() - 5, 1);
                        } else if (id === 'this_year') {
                            start = new Date(now.getFullYear(), 0, 1);
                            end = new Date(now.getFullYear(), 11, 31);
                        }

                        this.tempStart = this.toIso(start);
                        this.tempEnd = this.toIso(end);
                        this.viewMonth = end.getMonth();
                        this.viewYear = end.getFullYear();
                    },
                    selectDay(iso) {
                        this.activePreset = 'custom';
                        if (!this.selectingEnd) {
                            this.tempStart = iso;
                            this.tempEnd = iso;
                            this.selectingEnd = true;
                        } else {
                            if (iso < this.tempStart) {
                                this.tempEnd = this.tempStart;
                                this.tempStart = iso;
                            } else {
                                this.tempEnd = iso;
                            }
                            this.selectingEnd = false;
                        }
                    },
                    get calendarCells() {
                        const firstDay = new Date(this.viewYear, this.viewMonth, 1);
                        const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                        const startOffset = (firstDay.getDay() + 6) % 7; // Senin = 0 .. Minggu = 6
                        const cells = [];

                        for (let i = 0; i < startOffset; i++) {
                            cells.push({ empty: true, key: 'e-' + i });
                        }
                        for (let d = 1; d <= daysInMonth; d++) {
                            const iso = `${this.viewYear}-${String(this.viewMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                            cells.push({
                                empty: false,
                                key: iso,
                                day: d,
                                iso: iso,
                                isStart: iso === this.tempStart,
                                isEnd: iso === this.tempEnd,
                                inRange: iso > this.tempStart && iso < this.tempEnd
                            });
                        }
                        return cells;
                    },
                    applyRange() {
                        $wire.applyDateRange(this.tempStart, this.tempEnd, this.activePreset);
                        this.open = false;
                    }
                }"
                @keydown.escape.window="open = false"
                class="relative"
            >
                {{-- Trigger Button --}}
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-gray-900 hover:bg-gray-800 border border-white/10 text-sm font-semibold text-white shadow-sm transition"
                >
                    <svg width="18" height="18" class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ $stats['period_label'] }}</span>
                    <svg width="16" height="16" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Popover 2 Kolom (Dark Mode) --}}
                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-[340px] sm:w-[580px] rounded-2xl bg-gray-900 border border-white/10 shadow-2xl z-50 overflow-hidden"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-12 divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        {{-- Kolom Kiri: Preset Cepat --}}
                        <div class="sm:col-span-5 p-3 space-y-1 bg-gray-950/50">
                            <div class="px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                Preset Cepat
                            </div>
                            <template x-for="p in presets" :key="p.id">
                                <button
                                    type="button"
                                    @click="selectPreset(p.id)"
                                    class="w-full text-left px-3 py-2 rounded-lg text-xs font-semibold transition flex items-center justify-between"
                                    :class="activePreset === p.id
                                        ? 'bg-amber-500 text-gray-950 font-bold'
                                        : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                                >
                                    <span x-text="p.label"></span>
                                    <span x-show="activePreset === p.id" class="w-1.5 h-1.5 rounded-full bg-gray-950"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Kolom Kanan: Kalender Interaktif --}}
                        <div class="sm:col-span-7 p-4 flex flex-col justify-between bg-gray-900">
                            <div>
                                {{-- Navigasi Bulan & Tahun --}}
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <button
                                        type="button"
                                        @click="prevMonth()"
                                        class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 border border-white/10 transition"
                                    >
                                        <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>

                                    <div class="flex items-center gap-1.5">
                                        <select
                                            x-model.number="viewMonth"
                                            class="bg-gray-800 text-white text-xs font-semibold rounded-lg px-2.5 py-1.5 border border-white/10 focus:outline-none focus:border-amber-500"
                                        >
                                            <template x-for="(mName, mIdx) in monthNames" :key="mIdx">
                                                <option :value="mIdx" x-text="mName" :selected="mIdx === viewMonth" class="bg-gray-900 text-white"></option>
                                            </template>
                                        </select>

                                        <select
                                            x-model.number="viewYear"
                                            class="bg-gray-800 text-white text-xs font-semibold rounded-lg px-2.5 py-1.5 border border-white/10 focus:outline-none focus:border-amber-500"
                                        >
                                            <template x-for="yr in [2024, 2025, 2026, 2027]" :key="yr">
                                                <option :value="yr" x-text="yr" :selected="yr === viewYear" class="bg-gray-900 text-white"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <button
                                        type="button"
                                        @click="nextMonth()"
                                        class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 border border-white/10 transition"
                                    >
                                        <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Header Hari (Sen - Min) --}}
                                <div class="grid grid-cols-7 text-center text-[11px] font-bold text-gray-400 mb-1.5">
                                    <span>Sen</span>
                                    <span>Sel</span>
                                    <span>Rab</span>
                                    <span>Kam</span>
                                    <span>Jum</span>
                                    <span>Sab</span>
                                    <span>Min</span>
                                </div>

                                {{-- Grid Tanggal --}}
                                <div class="grid grid-cols-7 gap-1 text-center">
                                    <template x-for="cell in calendarCells" :key="cell.key">
                                        <div>
                                            <template x-if="cell.empty">
                                                <div class="h-8"></div>
                                            </template>
                                            <template x-if="!cell.empty">
                                                <button
                                                    type="button"
                                                    @click="selectDay(cell.iso)"
                                                    class="w-full h-8 rounded-lg text-xs font-semibold transition flex items-center justify-center"
                                                    :class="{
                                                        'bg-amber-500 text-gray-950 font-bold shadow': cell.isStart || cell.isEnd,
                                                        'bg-amber-500/20 text-amber-300': cell.inRange && !cell.isStart && !cell.isEnd,
                                                        'text-gray-200 hover:bg-white/10': !cell.isStart && !cell.isEnd && !cell.inRange
                                                    }"
                                                    x-text="cell.day"
                                                ></button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Footer Popover: Preview Rentang & Tombol Terapkan --}}
                            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between gap-2">
                                <div class="text-[11px] text-gray-400">
                                    <span class="font-semibold text-white" x-text="formatId(tempStart)"></span>
                                    <span>–</span>
                                    <span class="font-semibold text-white" x-text="formatId(tempEnd)"></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="open = false"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-300 hover:bg-white/5 transition"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyRange()"
                                        class="px-4 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-400 text-gray-950 shadow transition"
                                    >
                                        Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4 KPI Stats Cards (Full Dark Mode) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Views --}}
            <div class="rounded-2xl bg-gray-900 border border-white/10 p-5 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Views</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($stats['total_views']) }}</p>
                    <p class="mt-1 text-xs text-emerald-400 font-medium">Kunjungan halaman</p>
                </div>
                <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg width="24" height="24" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
            </div>

            {{-- Unique Visitors --}}
            <div class="rounded-2xl bg-gray-900 border border-white/10 p-5 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Unique Visitors</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($stats['unique_visitors']) }}</p>
                    <p class="mt-1 text-xs text-blue-400 font-medium">Pengunjung unik harian</p>
                </div>
                <div class="p-3 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <svg width="24" height="24" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Total Clicks --}}
            <div class="rounded-2xl bg-gray-900 border border-white/10 p-5 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Clicks</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($stats['total_clicks']) }}</p>
                    <p class="mt-1 text-xs text-amber-400 font-medium">Total klik tombol & banner</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg width="24" height="24" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                    </svg>
                </div>
            </div>

            {{-- CTR (%) --}}
            <div class="rounded-2xl bg-gray-900 border border-white/10 p-5 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">CTR (Click Rate)</p>
                    <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['ctr'] }}%</p>
                    <p class="mt-1 text-xs text-purple-400 font-medium">Rasio klik per kunjungan</p>
                </div>
                <div class="p-3 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg width="24" height="24" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Statistik Performa Pop-up Promo / Pengumuman (Full Dark Mode) --}}
        @php
            $popup = $stats['popup_stats'] ?? ['views' => 0, 'closes' => 0, 'clicks' => 0, 'conversion_rate' => 0.0, 'view_ctr' => 0.0];
        @endphp
        <div class="rounded-2xl bg-gray-900 border border-white/10 p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-pink-500/10 text-pink-400 border border-pink-500/20">
                        <svg width="20" height="20" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-white">Performa Pop-up Pengumuman / Promo</h3>
                        <p class="text-xs text-gray-400">Analisis respon pengunjung terhadap pop-up (Tampil, Ditutup, dan Klik CTA)</p>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-pink-500/10 text-pink-300 border border-pink-500/20">
                    <span class="w-2 h-2 rounded-full bg-pink-400"></span>
                    <span>Conversion Rate: {{ $popup['conversion_rate'] }}%</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Total Ditutup --}}
                <div class="rounded-xl border border-white/10 bg-gray-950/40 p-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Pop-up Ditutup (X / Luar)</p>
                        <p class="mt-1.5 text-2xl font-extrabold text-white">{{ number_format($popup['closes']) }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Pengunjung menutup modal pop-up</p>
                    </div>
                    <div class="p-2.5 rounded-lg bg-gray-800 border border-white/10 text-gray-400">
                        <svg width="20" height="20" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                {{-- Total Klik CTA --}}
                <div class="rounded-xl border border-white/10 bg-gray-950/40 p-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Klik CTA / Selengkapnya</p>
                        <p class="mt-1.5 text-2xl font-extrabold text-emerald-400">{{ number_format($popup['clicks']) }}</p>
                        <p class="text-[11px] text-emerald-400/80 mt-0.5">Pengunjung tertarik & klik aksi</p>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <svg width="20" height="20" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                </div>

                {{-- Rasio Konversi --}}
                <div class="rounded-xl border border-white/10 bg-gray-950/40 p-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Rasio Klik vs Tutup</p>
                        <p class="mt-1.5 text-2xl font-extrabold text-amber-400">{{ $popup['conversion_rate'] }}%</p>
                        <p class="text-[11px] text-amber-400/80 mt-0.5">{{ number_format($popup['clicks']) }} klik dari {{ number_format($popup['clicks'] + $popup['closes']) }} aksi</p>
                    </div>
                    <div class="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400">
                        <svg width="20" height="20" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Line / Area Chart: Tren Harian Views vs Clicks --}}
        <div class="rounded-2xl bg-gray-900 border border-white/10 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Tren Harian Views vs Clicks</h3>
                    <p class="text-xs text-gray-400">Perbandingan jumlah kunjungan halaman dan klik tombol per hari</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-emerald-400">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Views
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-amber-400">
                        <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span> Clicks
                    </span>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <svg viewBox="0 0 {{ $svgW }} {{ $svgH + 24 }}" class="w-full h-64 min-w-[540px]">
                    <defs>
                        <linearGradient id="viewsGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.32"/>
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0.0"/>
                        </linearGradient>
                        <linearGradient id="clicksGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.28"/>
                            <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>

                    {{-- Grid Horizontal --}}
                    @for ($g = 0; $g <= 4; $g++)
                        @php
                            $gy = round($padY + ($g / 4) * $plotH, 1);
                            $gVal = round($maxDaily * (1 - ($g / 4)));
                        @endphp
                        <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $svgW - $padX }}" y2="{{ $gy }}" stroke="rgba(255,255,255,0.08)" stroke-dasharray="4 4" stroke-width="1"/>
                        <text x="{{ $padX - 6 }}" y="{{ $gy + 4 }}" text-anchor="end" class="text-[10px] fill-gray-400">{{ $gVal }}</text>
                    @endfor

                    {{-- Area & Garis Views --}}
                    @if ($viewArea)
                        <polygon points="{{ $viewArea }}" fill="url(#viewsGrad)"/>
                        <polyline fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $viewPolyline }}"/>
                    @endif

                    {{-- Area & Garis Clicks --}}
                    @if ($clickArea)
                        <polygon points="{{ $clickArea }}" fill="url(#clicksGrad)"/>
                        <polyline fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $clickPolyline }}"/>
                    @endif

                    {{-- Titik Data & Label Tanggal --}}
                    @php
                        $stepLabel = max(1, (int) ceil($countDays / 10));
                    @endphp
                    @foreach ($daily as $idx => $item)
                        @php
                            $x = $countDays > 1
                                ? round($padX + ($idx / ($countDays - 1)) * $plotW, 1)
                                : round($svgW / 2, 1);
                            $yView = round($padY + $plotH - (($item['views'] / $maxDaily) * $plotH), 1);
                            $yClick = round($padY + $plotH - (($item['clicks'] / $maxDaily) * $plotH), 1);
                        @endphp
                        <circle cx="{{ $x }}" cy="{{ $yView }}" r="3.5" fill="#10b981">
                            <title>{{ $item['label'] }} — Views: {{ $item['views'] }}</title>
                        </circle>
                        <circle cx="{{ $x }}" cy="{{ $yClick }}" r="3.5" fill="#f59e0b">
                            <title>{{ $item['label'] }} — Clicks: {{ $item['clicks'] }}</title>
                        </circle>

                        @if ($idx % $stepLabel === 0 || $idx === $countDays - 1)
                            <text x="{{ $x }}" y="{{ $svgH + 14 }}" text-anchor="middle" class="text-[10px] fill-gray-400">
                                {{ $item['label'] }}
                            </text>
                        @endif
                    @endforeach
                </svg>
            </div>
        </div>

        {{-- Tabel Performa Tombol / Blok --}}
        <div class="rounded-2xl bg-gray-900 border border-white/10 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Peringkat Performa Tombol & Blok</h3>
                    <p class="text-xs text-gray-400">Daftar tombol tautan, WhatsApp, dan banner yang paling banyak diklik pengunjung</p>
                </div>
                <span class="text-xs font-semibold px-3 py-1 rounded-lg bg-gray-800 border border-white/10 text-gray-300">
                    Total {{ number_format($stats['total_clicks']) }} Klik
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            <th class="py-3 px-3 w-12">#</th>
                            <th class="py-3 px-3">Label Tombol / Blok</th>
                            <th class="py-3 px-3">Tipe Blok</th>
                            <th class="py-3 px-3 text-right">Jumlah Klik</th>
                            <th class="py-3 px-3 w-56">Proporsi Klik (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm">
                        @forelse ($stats['button_performance'] as $idx => $btn)
                            @php
                                $badgeClass = match ($btn['type']) {
                                    'whatsapp' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
                                    'banner' => 'bg-purple-500/10 text-purple-300 border-purple-500/20',
                                    'carousel' => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
                                    'popup' => 'bg-pink-500/10 text-pink-300 border-pink-500/20',
                                    default => 'bg-blue-500/10 text-blue-300 border-blue-500/20',
                                };
                                $typeLabel = match ($btn['type']) {
                                    'whatsapp' => 'WhatsApp',
                                    'banner' => 'Banner Event',
                                    'carousel' => 'Banner Slide',
                                    'popup' => 'Pop-up CTA',
                                    default => 'Tombol Link',
                                };
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-3.5 px-3 font-bold text-gray-400">
                                    #{{ $idx + 1 }}
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-white">{{ $btn['label'] }}</div>
                                    @if ($btn['target_url'])
                                        <div class="text-xs text-gray-400 truncate max-w-xs">{{ $btn['target_url'] }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                        {{ $typeLabel }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-right font-bold text-white">
                                    {{ number_format($btn['clicks']) }}
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full h-2 rounded-full bg-gray-800 overflow-hidden">
                                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $btn['percentage']) }}%;"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-300 w-12 text-right">
                                            {{ $btn['percentage'] }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm text-gray-400">
                                    Belum ada data klik pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sumber Trafik (Doughnut + Bar) & Breakdown Perangkat/OS --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Sumber Trafik (6 Kolom) --}}
            <div class="lg:col-span-6 rounded-2xl bg-gray-900 border border-white/10 p-6 shadow-sm">
                <h3 class="text-base font-bold text-white">Sumber Trafik (Referrer & UTM)</h3>
                <p class="text-xs text-gray-400 mb-5">Asal kedatangan pengunjung halaman DQWS</p>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                    {{-- SVG Doughnut Chart --}}
                    <div class="sm:col-span-5 flex justify-center">
                        @php
                            $radius = 48;
                            $circumference = 2 * M_PI * $radius;
                            $offset = 0;
                        @endphp
                        <div class="relative w-36 h-36 flex items-center justify-center">
                            <svg width="144" height="144" viewBox="0 0 120 120" class="w-36 h-36 -rotate-90">
                                <circle cx="60" cy="60" r="{{ $radius }}" fill="transparent" stroke="rgba(255,255,255,0.08)" stroke-width="14"/>
                                @foreach ($stats['referrers'] as $ref)
                                    @php
                                        $dash = ($ref['percentage'] / 100) * $circumference;
                                        $color = $sourceColors[$ref['source']] ?? '#10b981';
                                    @endphp
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="{{ $radius }}"
                                        fill="transparent"
                                        stroke="{{ $color }}"
                                        stroke-width="14"
                                        stroke-dasharray="{{ $dash }} {{ $circumference }}"
                                        stroke-dashoffset="{{ -$offset }}"
                                    />
                                    @php
                                        $offset += $dash;
                                    @endphp
                                @endforeach
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-lg font-extrabold text-white">{{ number_format($stats['total_views']) }}</span>
                                <span class="text-[10px] uppercase tracking-wider text-gray-400">Views</span>
                            </div>
                        </div>
                    </div>

                    {{-- Daftar Sumber Trafik --}}
                    <div class="sm:col-span-7 space-y-3">
                        @forelse ($stats['referrers'] as $ref)
                            @php
                                $color = $sourceColors[$ref['source']] ?? '#10b981';
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-gray-200">
                                        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: {{ $color }};"></span>
                                        {{ $ref['source'] }}
                                    </span>
                                    <span class="text-gray-400">
                                        {{ number_format($ref['count']) }} ({{ $ref['percentage'] }}%)
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-800 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ min(100, $ref['percentage']) }}%; background-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">Belum ada data sumber trafik pada periode ini.</p>
                        @endforelse
                    </div>
                </div>

                @if (! empty($stats['utm_campaigns']))
                    <div class="mt-6 pt-4 border-t border-white/10">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">Top UTM Source / Campaign</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($stats['utm_campaigns'] as $utm)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-800 border border-white/10 text-gray-300">
                                    <span>{{ $utm['name'] }}</span>
                                    <span class="font-bold text-emerald-400">({{ $utm['count'] }})</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Breakdown Perangkat, OS & Browser (6 Kolom) --}}
            <div class="lg:col-span-6 rounded-2xl bg-gray-900 border border-white/10 p-6 shadow-sm space-y-5">
                <div>
                    <h3 class="text-base font-bold text-white">Breakdown Perangkat & Sistem Operasi</h3>
                    <p class="text-xs text-gray-400">Distribusi jenis perangkat, OS, dan browser pengunjung</p>
                </div>

                {{-- Tipe Perangkat --}}
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">Tipe Perangkat</h4>
                    <div class="grid grid-cols-3 gap-3">
                        @forelse ($stats['devices'] as $dev)
                            <div class="rounded-xl border border-white/10 bg-gray-800/60 p-3 text-center">
                                <div class="text-xs font-semibold text-gray-400">{{ $dev['device'] }}</div>
                                <div class="text-lg font-extrabold text-white mt-0.5">{{ $dev['percentage'] }}%</div>
                                <div class="text-[11px] text-gray-400">{{ number_format($dev['count']) }} views</div>
                            </div>
                        @empty
                            <div class="col-span-3 text-xs text-gray-400">Belum ada data perangkat.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Sistem Operasi & Browser --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">Sistem Operasi (OS)</h4>
                        <div class="space-y-2.5">
                            @forelse ($stats['oses'] as $os)
                                <div>
                                    <div class="flex justify-between text-xs font-medium mb-1">
                                        <span class="text-gray-300">{{ $os['os'] }}</span>
                                        <span class="text-gray-400">{{ $os['count'] }} ({{ $os['percentage'] }}%)</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-gray-800 overflow-hidden">
                                        <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, $os['percentage']) }}%;"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">Belum ada data OS.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">Browser Teratas</h4>
                        <div class="space-y-2.5">
                            @forelse ($stats['browsers'] as $br)
                                <div>
                                    <div class="flex justify-between text-xs font-medium mb-1">
                                        <span class="text-gray-300">{{ $br['browser'] }}</span>
                                        <span class="text-gray-400">{{ $br['count'] }} ({{ $br['percentage'] }}%)</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-gray-800 overflow-hidden">
                                        <div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, $br['percentage']) }}%;"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">Belum ada data browser.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar Chart Jam Ramai (Peak Hours 00:00 - 23:00) --}}
        <div class="rounded-2xl bg-gray-900 border border-white/10 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-base font-bold text-white">Jam Ramai Pengunjung (Peak Hours 00:00 – 23:00)</h3>
                    <p class="text-xs text-gray-400">Distribusi waktu kunjungan dan klik berdasarkan jam dalam sehari</p>
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold">
                    <span>Jam Paling Ramai: <strong>{{ $stats['peak_hour_label'] }}</strong> ({{ $stats['peak_hour_count'] }} interaksi)</span>
                </div>
            </div>

            <div class="grid grid-cols-12 sm:grid-cols-24 gap-1.5 items-end h-44 pt-4">
                @foreach ($stats['peak_hours'] as $hr)
                    @php
                        $pct = round(($hr['total'] / max(1, $stats['max_hourly_total'])) * 100);
                        $isPeak = $hr['total'] > 0 && $hr['total'] === $stats['max_hourly_total'];
                    @endphp
                    <div class="flex flex-col items-center h-full justify-end group">
                        <div class="text-[10px] font-semibold text-gray-400 mb-1 opacity-0 group-hover:opacity-100 transition">
                            {{ $hr['total'] }}
                        </div>
                        <div class="w-full bg-gray-800 rounded-t-md h-32 flex items-end overflow-hidden">
                            <div
                                title="{{ $hr['hour'] }} — Views: {{ $hr['views'] }}, Clicks: {{ $hr['clicks'] }}"
                                class="w-full rounded-t-md transition-all duration-200 {{ $isPeak ? 'bg-amber-500' : 'bg-emerald-500/80 hover:bg-emerald-500' }}"
                                style="height: {{ max($hr['total'] > 0 ? 8 : 2, $pct) }}%;"
                            ></div>
                        </div>
                        <span class="mt-1.5 text-[10px] font-medium text-gray-400">
                            {{ $hr['short'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    @vite('resources/css/app.css')

    <div class="space-y-8" x-data="{ copiedId: null }">
        {{-- Form Multi-Upload --}}
        <form wire:submit="uploadMedia" class="space-y-4">
            {{ $this->form }}

            <div>
                <x-filament::button
                    type="submit"
                    color="primary"
                    icon="heroicon-o-cloud-arrow-up"
                >
                    Upload & Simpan ke Pustaka Media
                </x-filament::button>
            </div>
        </form>

        {{-- Filter & Pencarian Pustaka Media --}}
        <div class="rounded-2xl bg-gray-900 border border-white/10 p-5 shadow-lg space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/10 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Daftar Media Tersimpan ({{ $this->mediaItems->count() }})</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Klik "Salin URL" untuk menggunakan link gambar atau video di blok Page Builder.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Filter Tipe --}}
                    <div class="inline-flex rounded-xl bg-gray-950 p-1 border border-white/10">
                        <button
                            type="button"
                            wire:click="$set('filterType', 'all')"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'all' ? 'bg-emerald-600 text-white shadow' : 'text-gray-400 hover:text-white' }}"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            wire:click="$set('filterType', 'image')"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'image' ? 'bg-emerald-600 text-white shadow' : 'text-gray-400 hover:text-white' }}"
                        >
                            Gambar
                        </button>
                        <button
                            type="button"
                            wire:click="$set('filterType', 'video')"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'video' ? 'bg-emerald-600 text-white shadow' : 'text-gray-400 hover:text-white' }}"
                        >
                            Video
                        </button>
                    </div>

                    {{-- Input Cari Media --}}
                    <div class="relative">
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="searchQuery"
                            placeholder="Cari nama media..."
                            class="w-48 sm:w-60 pl-3.5 pr-8 py-1.5 rounded-xl bg-gray-950 border border-white/10 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500"
                        />
                        @if ($searchQuery !== '')
                            <button
                                type="button"
                                wire:click="$set('searchQuery', '')"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 hover:text-white"
                            >
                                &#10005;
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Grid Media --}}
            @if ($this->mediaItems->isEmpty())
                <div class="py-12 text-center">
                    <div class="mx-auto w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-3">
                        <svg width="24" height="24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-300">Belum ada file media yang tersimpan</p>
                    <p class="text-xs text-gray-500 mt-1">Gunakan form di atas untuk mengupload banyak gambar atau video sekaligus.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($this->mediaItems as $item)
                        @php
                            $fullUrl = asset('storage/' . ltrim($item->file_path, '/'));
                        @endphp
                        <div
                            wire:key="media-{{ $item->id }}"
                            class="group rounded-xl bg-gray-950 border border-white/10 overflow-hidden flex flex-col justify-between shadow hover:border-emerald-500/40 transition"
                        >
                            {{-- Preview Thumbnail --}}
                            <div class="relative w-full aspect-video bg-black/60 overflow-hidden flex items-center justify-center">
                                @if ($item->file_type === 'video')
                                    <video
                                        src="{{ $fullUrl }}"
                                        controls
                                        preload="metadata"
                                        class="w-full h-full object-contain"
                                    ></video>
                                @else
                                    <img
                                        src="{{ $fullUrl }}"
                                        alt="{{ $item->title }}"
                                        loading="lazy"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    />
                                @endif

                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $item->file_type === 'video' ? 'bg-purple-600/90 text-white' : 'bg-emerald-600/90 text-white' }}">
                                    {{ $item->file_type === 'video' ? 'VIDEO' : 'IMAGE' }}
                                </span>
                            </div>

                            {{-- Detail & Tombol Aksi --}}
                            <div class="p-3.5 space-y-3">
                                <div>
                                    <p class="text-xs font-bold text-white truncate" title="{{ $item->title }}">
                                        {{ $item->title ?: basename($item->file_path) }}
                                    </p>
                                    <div class="mt-1 flex items-center justify-between text-[11px] text-gray-400">
                                        <span>{{ $item->formatted_size }}</span>
                                        <span>{{ $item->created_at?->format('d M Y H:i') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 pt-1">
                                    <button
                                        type="button"
                                        @click="
                                            navigator.clipboard.writeText(@js($fullUrl));
                                            copiedId = {{ $item->id }};
                                            setTimeout(() => { if (copiedId === {{ $item->id }}) copiedId = null; }, 2000);
                                        "
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                        :class="copiedId === {{ $item->id }}
                                            ? 'bg-emerald-500 text-gray-950'
                                            : 'bg-white/10 hover:bg-white/15 text-white border border-white/10'"
                                    >
                                        <span x-show="copiedId !== {{ $item->id }}">Salin URL</span>
                                        <span x-show="copiedId === {{ $item->id }}" x-cloak>&#10003; Tersalin!</span>
                                    </button>

                                    <a
                                        href="{{ $fullUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="p-1.5 rounded-lg bg-white/5 hover:bg-white/15 text-gray-300 hover:text-white border border-white/10 transition"
                                        title="Buka File di Tab Baru"
                                    >
                                        <svg width="15" height="15" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                        </svg>
                                    </a>

                                    <button
                                        type="button"
                                        wire:click="deleteMedia({{ $item->id }})"
                                        wire:confirm="Hapus file media ini secara permanen?"
                                        class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/25 text-red-400 hover:text-red-300 border border-red-500/20 transition"
                                        title="Hapus Media"
                                    >
                                        <svg width="15" height="15" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

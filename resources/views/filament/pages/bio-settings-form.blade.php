<x-filament-panels::page>
    @vite('resources/css/app.css')
    <div class="max-w-3xl">
        <form wire:submit="saveAndPublish" class="space-y-6">
            {{ $this->form }}

            <div class="pt-2 flex items-center gap-3">
                <x-filament::button
                    type="submit"
                    color="primary"
                    icon="heroicon-o-check-circle"
                >
                    Simpan & Publish
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Pages\BioPageBuilder::getUrl() }}"
                    color="gray"
                    icon="heroicon-o-device-phone-mobile"
                >
                    Lihat di Page Builder
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>

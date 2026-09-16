<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div style="margin-top: 24px">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan pengaturan</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

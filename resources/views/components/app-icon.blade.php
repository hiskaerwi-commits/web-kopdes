@props(['name' => 'arrow'])
@php
    $paths = [
        'arrow' => 'M5 12h14m-6-6 6 6-6 6',
        'left' => 'M19 12H5m6-6-6 6 6 6',
        'external' => 'M7 17 17 7M7 7h10v10',
        'chevron' => 'm6 9 6 6 6-6',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'search' => 'm21 21-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
        'download' => 'M12 3v12m-5-5 5 5 5-5M4 15v5h16v-5',
        'pin' => 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0ZM15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
        'document' => 'M14 2H5v20h14V7l-5-5Zm0 0v6h5M8 12h8M8 16h6',
        'play' => 'm9 7 8 5-8 5V7ZM3 5h18v14H3V5Z',
        'instagram' => 'M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4ZM16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0M17 7h.01',
        'phone' => 'M6 3H3v3c0 8 7 15 15 15h3v-5l-5-2-2 3a16 16 0 0 1-7-7l3-2-2-5H6Z',
        'chart' => 'M4 20V10m6 10V4m6 16v-7',
    ];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['arrow'] }}" /></svg>

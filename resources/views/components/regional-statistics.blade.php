@props(['summary', 'cards', 'heading' => true])
@if($heading)
    <div class="section-heading">
        <div><p class="eyebrow">DATA KOPDES / KDMP</p><h2>{{ $summary['name'] }}</h2></div>
        <a class="text-link" href="https://simkopdes.go.id/pers/dashboard" target="_blank" rel="noopener noreferrer">Sumber Simkopdes <x-app-icon name="external" /></a>
    </div>
@endif
@if(count($cards))
    <div class="statistics-grid">@foreach($cards as $card)<article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">{{ $card['label'] }}</p><strong>{{ $card['value'] }}</strong></article>@endforeach</div>
    <p class="source-note">Wilayah data: <strong>{{ $summary['name'] }}</strong>. Salinan API Simkopdes per {{ \Carbon\Carbon::parse($summary['fetched_at'])->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB. Angka ditampilkan sesuai cakupan dan definisi sumber; bukan jumlah hasil penjumlahan wilayah induk dan turunannya.</p>
@else
    <div class="empty-state"><h3>Data {{ $summary['name'] }} belum tersedia</h3><p>Statistik akan ditampilkan setelah data untuk wilayah ini diperbarui.</p></div>
@endif

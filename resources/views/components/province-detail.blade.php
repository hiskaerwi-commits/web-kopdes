@props(['detail'])
@php($t = $detail['totals'])
<div class="province-detail">
    <div class="section-heading">
        <h2>Detail Kabupaten/Kota</h2>
        <p>Rincian koperasi di setiap kabupaten/kota wilayah {{ $detail['name'] }}.</p>
    </div>

    <div class="statistics-grid">
        <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Total Koperasi</p><strong>{{ number_format($t['cooperatives'], 0, ',', '.') }}</strong></article>
        <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Memiliki Akun</p><strong>{{ number_format($t['accounts'], 0, ',', '.') }}</strong></article>
        <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Memiliki NPWP</p><strong>{{ number_format($t['npwp'], 0, ',', '.') }}</strong></article>
        <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Memiliki NIB</p><strong>{{ number_format($t['nib'], 0, ',', '.') }}</strong></article>
    </div>

    <h3 class="section-subheading">Modal Koperasi</h3>
    <div class="statistics-grid">
        <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Simpanan Pokok</p><strong>Rp {{ number_format($t['simpanan_pokok']['total_amount'], 0, ',', '.') }}</strong></article>
        <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Simpanan Wajib</p><strong>Rp {{ number_format($t['simpanan_wajib']['total_amount'], 0, ',', '.') }}</strong></article>
    </div>

    @if($detail['economic_impact'])
        <h3 class="section-subheading">Dampak Ekonomi</h3>
        <div class="statistics-grid">
            <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Volume Transaksi</p><strong>{{ number_format($t['transaction_volume'], 0, ',', '.') }}</strong></article>
            <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">Nilai Transaksi</p><strong>Rp {{ number_format($t['transaction_value'], 0, ',', '.') }}</strong></article>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead><tr><th>No</th><th>Nama Produk</th><th class="num">Volume</th><th class="num">Nilai Transaksi</th></tr></thead>
                <tbody>
                    @foreach($detail['economic_impact']['rows'] as $row)
                        <tr><td>{{ $loop->iteration }}</td><td>{{ $row['product'] }}</td><td class="num">{{ number_format($row['volume'], 0, ',', '.') }}</td><td class="num">Rp {{ number_format($row['value'], 0, ',', '.') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($detail['rat_summary'])
        <h3 class="section-subheading">Aktivitas Rapat Anggota Tahunan ({{ $detail['rat_summary']['period'] }})</h3>
        <div class="pd-rat-grid">
            <article class="pd-rat-card is-done"><strong>{{ number_format($detail['rat_summary']['total_verified_rat'], 0, ',', '.') }}</strong><span>Terverifikasi</span></article>
            <article class="pd-rat-card is-draft"><strong>{{ number_format($detail['rat_summary']['total_draft_rat'], 0, ',', '.') }}</strong><span>Draft</span></article>
            <article class="pd-rat-card is-none"><strong>{{ number_format($detail['rat_summary']['total_no_rat'], 0, ',', '.') }}</strong><span>Belum RAT</span></article>
        </div>
    @endif

    @if(count($detail['store_readiness']))
        <h3 class="section-subheading">Kesiapan Pembangunan Gerai</h3>
        <div class="statistics-grid">
            @foreach($detail['store_readiness'] as $bucket)
                <article><span class="stat-icon"><x-app-icon name="chart" /></span><p class="stat-label">{{ $bucket['label'] }}</p><strong>{{ number_format($bucket['value'], 0, ',', '.') }}</strong></article>
            @endforeach
        </div>
    @endif

    <h3 class="section-subheading">Data Kabupaten/Kota di {{ $detail['name'] }}</h3>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead><tr><th>No</th><th>Kabupaten/Kota</th><th class="num">Koperasi</th><th class="num">NIB</th><th class="num">NPWP</th><th class="num">RAT</th><th class="num">Simpanan Pokok</th><th class="num">Simpanan Wajib</th><th class="num">Volume Transaksi</th></tr></thead>
            <tbody>
                @foreach($detail['districts'] as $district)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $district['name'] }}</td>
                        <td class="num">{{ number_format($district['cooperatives'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($district['nib'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($district['npwp'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($district['rat'], 0, ',', '.') }}</td>
                        <td class="num">Rp {{ number_format($district['simpanan_pokok']['total_amount'], 0, ',', '.') }}</td>
                        <td class="num">Rp {{ number_format($district['simpanan_wajib']['total_amount'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($district['transaction_volume'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="source-note">Sumber: {{ $detail['source'] }}. Diperbarui {{ \Carbon\Carbon::parse($detail['fetched_at'])->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB.</p>
</div>

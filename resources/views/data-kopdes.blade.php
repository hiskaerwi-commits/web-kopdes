<x-layout
    :settings="$settings"
    :title="'Data Kopdes — '.$summary['name']"
    :description="'Ringkasan aktivitas koperasi wilayah '.$summary['name'].' berdasarkan sumber data Simkopdes: akun koperasi, gerai aktif, NPWP, NIB, dan RAT terkini.'"
>
    <section class="page-banner">
        <div class="container">
            <p class="eyebrow">DATA KOPDES / KDMP</p>
            <h1>Data Koperasi {{ $summary['name'] }}</h1>
            <p>Ringkasan aktivitas koperasi wilayah {{ $summary['name'] }} berdasarkan sumber data Simkopdes.</p>
        </div>
    </section>
    <section class="container section">
        @unless($settings->scopeCode())
            <form class="search-form" method="get" action="{{ route('data-kopdes') }}"><label for="data-province">Pilih cakupan data</label><div><select id="data-province" name="province" class="search-input"><option value="">Nasional</option>@foreach($provinces as $province)<option value="{{ $province['code'] }}" @selected($summary['code'] === $province['code'])>{{ $province['name'] }}</option>@endforeach</select><button type="submit" class="button">Tampilkan</button></div></form>
        @endunless

        <div class="section-heading">
            <p class="eyebrow">STATISTIK WILAYAH</p>
            <a class="text-link" href="https://simkopdes.go.id/pers/dashboard" target="_blank" rel="noopener noreferrer">Sumber Simkopdes <x-app-icon name="external" /></a>
        </div>
        <x-regional-statistics :summary="$summary" :cards="$statCards" :heading="false" />

        @if(count($summary['rows']))
            <h2 class="section-subheading">Ringkasan per Provinsi</h2>
            <div class="data-table-wrap">
                <table class="data-table">
                    <caption class="sr-only">Ringkasan per provinsi</caption>
                    <thead><tr><th scope="col">Provinsi</th><th scope="col" class="num">Akun koperasi</th><th scope="col" class="num">Gerai aktif</th><th scope="col" class="num">Anggota tercatat</th></tr></thead>
                    <tbody>
                        @foreach($summary['rows'] as $row)
                            <tr><th scope="row"><a href="{{ route('data-kopdes', ['province' => $row['code']]) }}">{{ $row['name'] }}</a></th><td class="num">{{ number_format($row['metrics']['accounts'], 0, ',', '.') }}</td><td class="num">{{ number_format($row['metrics']['active_outlets'], 0, ',', '.') }}</td><td class="num">{{ number_format($row['metrics']['members'], 0, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($provinceDetail)
            <x-province-detail :detail="$provinceDetail" />
        @endif
    </section>
</x-layout>

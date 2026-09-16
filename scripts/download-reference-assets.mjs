import fs from 'node:fs/promises';
const directory = 'public/images/simkopdes';
await fs.mkdir(directory, { recursive: true });
const paths = [
    'simkopdes-white.webp', 'logo.webp', 'new-landing-page/hero.webp', 'new-landing-page/aggregator.webp',
    'sejahtera.webp', 'kerja.webp', 'pelayanan.webp', 'berdaya.webp', 'digital-koperasi.webp', 'menekan-harga.webp',
    'bg-login.webp', 'tengkulak.webp', 'pemerataan-ekonomi.webp', 'inklusi.webp', 'umkm.webp', 'nelayan.webp', 'rupiah.webp',
    'new-landing-page/app-download/app-store.svg', 'new-landing-page/app-download/play-store.svg',
    'new-landing-page/app-download/podium.webp', 'new-landing-page/app-download/phone-right.webp', 'new-landing-page/app-download/phone-left.webp',
    'new-landing-page/footer/organizations.webp', 'new-landing-page/footer/pedoman.webp',
];
const results = [];
for (const asset of paths) {
    const url = `https://simkopdes.go.id/images/${asset}`;
    const response = await fetch(url);
    if (!response.ok || !response.headers.get('content-type')?.startsWith('image/')) throw new Error(`Invalid asset ${url}: ${response.status}`);
    const data = Buffer.from(await response.arrayBuffer());
    const file = asset.split('/').at(-1);
    await fs.writeFile(`${directory}/${file}`, data);
    results.push({file, source: url, bytes: data.length});
}
await fs.writeFile(`${directory}/sources.json`, JSON.stringify({retrieved_on: '2026-09-14', assets: results}, null, 2));
const page = JSON.parse(await fs.readFile('storage/app/reference/page.json', 'utf8'));
for (const [index, asset] of page.images.filter(image => image.src.includes('/courses/')).entries()) {
    const response = await fetch(asset.src);
    if (!response.ok || !response.headers.get('content-type')?.startsWith('image/')) throw new Error(`Course image failed: ${response.status}`);
    const data = Buffer.from(await response.arrayBuffer());
    const file = `course-${index + 1}.jpg`;
    await fs.writeFile(`${directory}/${file}`, data);
    results.push({ file, source: asset.src.split('?')[0], bytes: data.length });
}
await fs.writeFile(`${directory}/sources.json`, JSON.stringify({retrieved_on: '2026-09-14', assets: results}, null, 2));
console.log(`Downloaded ${results.length} reference assets.`);

import fs from 'node:fs/promises';
const page = JSON.parse(await fs.readFile('storage/app/reference/page.json', 'utf8'));
const titles = page.headings.filter(item => item.text.startsWith('KOPERASI DESA MERAH PUTIH'));
const links = page.links.filter(item => item.text === 'Lihat Situs');
const data = {
    source: 'https://simkopdes.go.id/', retrieved_on: '2026-09-14',
    networks: titles.map((item, i) => ({name: item.text, region: 'Direktori nasional Simkopdes', url: links[i]?.url})),
    courses: page.images.filter(item => item.src.includes('/courses/')).map((item, i) => ({
        title: ['Dasar-dasar perkoperasian', 'Aset digital koperasi: Banten', 'Mengenal tata kelola koperasi'][i],
        description: ['Pelajari konsep, kelembagaan, dan pengelolaan usaha koperasi.', 'Kembangkan kemampuan pengelolaan dan promosi koperasi melalui teknologi.', 'Bangun pemahaman tentang peran anggota dan pengelolaan koperasi.'][i],
        image: `course-${i + 1}.jpg`, url: page.links.find(link => link.text.includes(item.alt))?.url,
    })),
};
await fs.mkdir('resources/data', {recursive: true});
await fs.writeFile('resources/data/simkopdes.json', JSON.stringify(data, null, 2));
console.log(`Saved ${data.networks.length} directory entries and ${data.courses.length} learning links.`);

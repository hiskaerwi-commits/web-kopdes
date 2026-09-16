import puppeteer from 'puppeteer';
import fs from 'node:fs/promises';

const directory = 'storage/app/api-discovery';
await fs.mkdir(directory, { recursive: true });
const browser = await puppeteer.launch({ headless: true, pipe: true, executablePath: process.env.CHROME_PATH || undefined, userDataDir: 'storage/framework/api-discovery-browser', timeout: 25000 });
const traffic = [];
const bundles = new Map();
const pending = [];
try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    page.on('response', response => {
        const url = response.url();
        if (url.includes('/_next/static/') && url.endsWith('.js')) pending.push(response.text().then(text => bundles.set(url, text)).catch(() => {}));
        if (new URL(url).hostname !== 'api.simkopdes.go.id') return;
        const entry = { url, method: response.request().method(), status: response.status() };
        traffic.push(entry);
        pending.push(response.json().then(body => {
            entry.keys = Object.keys(body || {});
            const data = body?.data;
            entry.data_keys = data && typeof data === 'object' ? Object.keys(data).slice(0, 25) : [];
            const items = Array.isArray(data) ? data : data?.data;
            entry.item_keys = Array.isArray(items) && items[0] ? Object.keys(items[0]) : [];
            entry.total = body?.total ?? data?.total ?? body?.meta?.total;
        }).catch(() => {}));
    });
    for (const route of ['/', '/pers/dashboard']) {
        await page.goto(`https://simkopdes.go.id${route}`, { waitUntil: 'networkidle2', timeout: 60000 });
        await page.evaluate(async () => {
            for (let y = 0; y < document.body.scrollHeight; y += 850) {
                window.scrollTo(0, y);
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        });
        await new Promise(resolve => setTimeout(resolve, 3500));
    }
    await Promise.allSettled(pending);
    const snippets = [];
    for (const [url, code] of bundles) {
        for (const match of code.matchAll(/.{0,120}(?:api\.simkopdes\.go\.id|\/provinces|\/regencies|\/districts|\/villages|\/cooperatives|province_id|province_code|regency_id|village_id).{0,180}/g)) {
            snippets.push({bundle: url, code: match[0]});
        }
    }
    await fs.writeFile(`${directory}/traffic.json`, JSON.stringify(traffic, null, 2));
    await fs.writeFile(`${directory}/snippets.json`, JSON.stringify(snippets, null, 2));
    for (const [index, [url, code]] of [...bundles].entries()) await fs.writeFile(`${directory}/bundle-${index}.js`, code);
    await fs.writeFile(`${directory}/bundles.json`, JSON.stringify([...bundles.keys()], null, 2));
    console.log(JSON.stringify({traffic, snippets}, null, 2));
} finally { await browser.close(); }

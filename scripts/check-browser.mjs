import puppeteer from 'puppeteer';
import fs from 'node:fs/promises';

const browser = await puppeteer.launch({ headless: true, pipe: true, timeout: 20000, executablePath: process.env.CHROME_PATH || undefined, userDataDir: 'storage/framework/browser-preview' });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('response', response => { if (response.status() >= 400) errors.push(`${response.status()} ${response.url()}`); });
try {
    await fs.mkdir('storage/app/preview', { recursive: true });
    await page.setViewport({ width: 1440, height: 1000 });
    await page.goto('http://127.0.0.1:8001', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: 'storage/app/preview/desktop.png', fullPage: true });
    for (const width of [390, 320]) {
        await page.setViewport({ width, height: 844, isMobile: true, hasTouch: true });
        await page.goto('http://127.0.0.1:8001', { waitUntil: 'networkidle0' });
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
        if (overflow) throw new Error(`Horizontal overflow at ${width}px`);
        await page.click('.menu-toggle');
        if (await page.$eval('.menu-toggle', el => el.getAttribute('aria-expanded')) !== 'true') throw new Error('Menu did not open');
        await page.click('#navigation a');
        if (await page.$eval('.menu-toggle', el => el.getAttribute('aria-expanded')) !== 'false') throw new Error('Menu did not close');
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path: `storage/app/preview/mobile-${width}.png`, fullPage: true });
    }
    await page.goto('http://127.0.0.1:8001/berita?q=contoh', { waitUntil: 'networkidle0' });
    await page.goto('http://127.0.0.1:8001/admin/login', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: 'storage/app/preview/admin-login.png', fullPage: true });
    if (errors.length) throw new Error(errors.join('\n'));
    console.log('Browser checks passed: desktop, mobile 390/320, menu, news, admin login, assets.');
} finally {
    await browser.close();
}

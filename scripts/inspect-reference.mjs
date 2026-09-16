import puppeteer from 'puppeteer';
import fs from 'node:fs/promises';

const directory = 'storage/app/reference';
await fs.mkdir(directory, { recursive: true });
const browser = await puppeteer.launch({ headless: true, pipe: true, executablePath: process.env.CHROME_PATH || undefined, userDataDir: 'storage/framework/reference-browser', timeout: 25000 });
try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    await page.goto('https://simkopdes.go.id/', { waitUntil: 'networkidle2', timeout: 60000 });
    await new Promise(resolve => setTimeout(resolve, 3000));
    await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 600) {
            window.scrollTo(0, y);
            await new Promise(resolve => setTimeout(resolve, 180));
        }
        window.scrollTo(0, 0);
    });
    await new Promise(resolve => setTimeout(resolve, 1000));
    await page.screenshot({ path: `${directory}/desktop.png`, fullPage: true });
    const data = await page.evaluate(() => ({
        title: document.title, text: document.body.innerText,
        videos: [...document.querySelectorAll('video,video source')].map(el => ({src: el.src, poster: el.poster})),
        buttons: [...document.querySelectorAll('button')].map(el => ({text:el.innerText, html:el.outerHTML.slice(0,600)})),
        links: [...document.querySelectorAll('a')].map(el => ({ text: el.innerText, url: el.href })),
        images: [...document.images].map(el => ({ alt: el.alt, src: el.currentSrc || el.src, width: el.naturalWidth, height: el.naturalHeight })),
        backgrounds: [...document.querySelectorAll('*')].map(el => getComputedStyle(el).backgroundImage).filter(value => value !== 'none'),
        headings: [...document.querySelectorAll('h1,h2,h3')].map(el => ({ text: el.innerText, font: getComputedStyle(el).fontFamily, size: getComputedStyle(el).fontSize, color: getComputedStyle(el).color })),
    }));
    await fs.writeFile(`${directory}/page.json`, JSON.stringify(data, null, 2));
    await fs.writeFile(`${directory}/page.html`, await page.content());
    console.log(JSON.stringify(data, null, 2));
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await page.screenshot({ path: `${directory}/mobile.png`, fullPage: true });
} finally { await browser.close(); }

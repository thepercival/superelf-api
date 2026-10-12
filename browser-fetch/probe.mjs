import { chromium } from 'playwright';
const U = 'https://www.sofascore.com/api/v1/sport/football/categories';
const b = await chromium.launch({ headless: true, channel: 'chromium' });
const p = await (await b.newContext({ locale: 'en-GB' })).newPage();
const r = await p.goto(U, { waitUntil: 'domcontentloaded' });
console.log('playwright goto www path', r.status(), (await r.text()).slice(0, 60));
await b.close();

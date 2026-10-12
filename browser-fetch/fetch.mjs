// Usage: node fetch.mjs <url>
// Optional proxy via env: FETCH_PROXY_HOST, FETCH_PROXY_PORT, FETCH_PROXY_USERNAME, FETCH_PROXY_PASSWORD
// Writes the response body to stdout; exits non-zero (message on stderr) when the status is not 200.
import { chromium } from 'playwright';

const inputUrl = process.argv[2];
if (!inputUrl) {
    console.error('missing url');
    process.exit(2);
}
// api.sofascore.com blocks browser navigation; the same path on www is served
const url = inputUrl.replace(/^https?:\/\/api\.sofascore\.com\//, 'https://www.sofascore.com/');

const env = process.env;
const proxy = env.FETCH_PROXY_HOST
    ? {
          server: `http://${env.FETCH_PROXY_HOST}:${env.FETCH_PROXY_PORT}`,
          username: env.FETCH_PROXY_USERNAME,
          password: env.FETCH_PROXY_PASSWORD,
      }
    : undefined;

// channel 'chromium' = new headless mode; the legacy headless shell is blocked
const browser = await chromium.launch({ headless: true, channel: 'chromium', proxy });
try {
    const context = await browser.newContext({ locale: 'en-GB' });
    const page = await context.newPage();
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    if (response === null || response.status() !== 200) {
        console.error(`status ${response === null ? 'none' : response.status()} for ${url}`);
        process.exitCode = 1;
    } else {
        process.stdout.write(await response.text());
    }
} catch (e) {
    console.error(String(e && e.message ? e.message : e));
    process.exitCode = 1;
} finally {
    await browser.close();
}

import { readFile, writeFile, mkdir, access } from 'node:fs/promises';
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';
import { spawn } from 'node:child_process';

// Render the website's actual CSS, including its pseudo-elements and blur.
// Run on Windows after changing the premium header styles.
const root = fileURLToPath(new URL('../../', import.meta.url));
const source = await readFile(path.join(root, 'src/css/app.scss'), 'utf8');
const start = source.indexOf('.sn-header--premium {');
const end = source.indexOf('.sn-header--premium > *', start);
if (start < 0 || end < 0) throw new Error('Premium header styles not found');
const css = source.slice(start, end);
const temporary = path.join(root, 'tmp/pdfs');
await mkdir(temporary, { recursive: true });
const html = path.join(temporary, 'store-header.html');
await writeFile(html, `<!doctype html><meta charset="utf-8"><style>
html,body{margin:0;width:1400px;height:220px;overflow:hidden}
${css}
.sn-header--premium{width:1400px;height:220px;box-shadow:none!important}
</style><div class="sn-header--premium"></div>`);
const candidates = [process.env.CHROME_PATH,
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'].filter(Boolean);
let browser;
for (const candidate of candidates) {
  try { await access(candidate); browser = candidate; break; } catch {}
}
if (!browser) throw new Error('Set CHROME_PATH to a Chromium browser');
const output = path.join(root, 'apis/assets/header-store.png');
await new Promise((resolve, reject) => {
  const child = spawn(browser, ['--headless=new', '--no-first-run',
    '--disable-background-networking', '--hide-scrollbars',
    '--force-device-scale-factor=2', '--window-size=1400,220',
    `--user-data-dir=${path.join(temporary, 'header-browser-profile')}`,
    `--screenshot=${output}`, pathToFileURL(html).href], { windowsHide: true, stdio: 'ignore' });
  child.once('error', reject);
  child.once('exit', code => code === 0 ? resolve() : reject(new Error(`Browser exited ${code}`)));
});
await access(output);
console.log('Generated apis/assets/header-store.png from src/css/app.scss');

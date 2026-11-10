import { chromium } from 'file:///C:/Users/Andru/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/.pnpm/playwright-core@1.60.0/node_modules/playwright-core/index.mjs'
import { mkdir, writeFile } from 'node:fs/promises'

const outputDirectory = new URL('./category-menu-qa/', import.meta.url)
await mkdir(outputDirectory, { recursive: true })
const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
})
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 })
const errors = []
page.on('pageerror', (error) => errors.push(error.message))
await page.goto('http://localhost:9000/ImportadoraSupernova/', { waitUntil: 'domcontentloaded' })
await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {})
await page.screenshot({ path: new URL('initial-desktop.png', outputDirectory).pathname.replace(/^\//, '') })
const snapshot = await page.locator('body').innerText()
await writeFile(new URL('initial-desktop.txt', outputDirectory), snapshot)
console.log(JSON.stringify({ snapshot: snapshot.slice(0, 14000), errors }))
await browser.close()

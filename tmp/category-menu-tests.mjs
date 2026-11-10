import { chromium } from 'file:///C:/Users/Andru/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/.pnpm/playwright-core@1.60.0/node_modules/playwright-core/index.mjs'
import { mkdir, writeFile } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'

const output = new URL('./category-menu-qa/', import.meta.url)
await mkdir(output, { recursive: true })
const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true })
const page = await browser.newPage({ viewport: { width: 1396, height: 917 }, deviceScaleFactor: 1 })
const errors = []
const results = []
page.on('pageerror', error => errors.push(error.message))
const capture = async name => page.screenshot({ path: fileURLToPath(new URL(name + '.png', output)) })
const measure = async () => page.evaluate(() => {
  const menu = document.querySelector('.q-menu')
  const rect = menu?.getBoundingClientRect()
  return { width: innerWidth, height: innerHeight, documentWidth: document.documentElement.scrollWidth, menu: menu ? { left: rect.left, top: rect.top, right: rect.right, bottom: rect.bottom, clientWidth: menu.clientWidth, scrollWidth: menu.scrollWidth, clientHeight: menu.clientHeight, scrollHeight: menu.scrollHeight } : null }
})
await page.goto('http://localhost:9000/ImportadoraSupernova/', { waitUntil: 'domcontentloaded' })
await page.screenshot({ path: fileURLToPath(new URL('home-1396.png', output)) })
await page.getByRole('button', { name: 'Todas las categorías', exact: true }).click()
await page.locator('.q-menu').waitFor()
await page.locator('.q-menu .catalog-menu__campaign-photo').waitFor()
await capture('popup-1396')
results.push({ test: 'popup1396', dimensions: await measure(), tiles: await page.locator('.q-menu .catalog-menu__tile').count(), products: await page.locator('.q-menu .catalog-menu__product').count() })
console.log(JSON.stringify(results.at(-1)))
await writeFile(new URL('early-report.json', output), JSON.stringify(results, null, 2))
await browser.close()

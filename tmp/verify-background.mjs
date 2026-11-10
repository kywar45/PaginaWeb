import { chromium } from 'file:///C:/Users/Andru/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/.pnpm/playwright-core@1.60.0/node_modules/playwright-core/index.mjs'
import { fileURLToPath } from 'node:url'

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
})
try {
  const page = await browser.newPage()
  const errors = []
  page.on('pageerror', error => errors.push(error.message))
  await page.goto('http://localhost:9000/ImportadoraSupernova/', { waitUntil: 'networkidle' })
  await page.locator('.banner-loading').waitFor({ state: 'hidden', timeout: 20000 })
  for (const viewport of [{ width: 1544, height: 1017 }, { width: 390, height: 844 }]) {
    await page.setViewportSize(viewport)
    await page.screenshot({ path: fileURLToPath(new URL(`./background-${viewport.width}.png`, import.meta.url)) })
    console.log(await page.evaluate(() => ({
      width: innerWidth,
      background: getComputedStyle(document.querySelector('.store-home')).backgroundImage,
      contentWidth: document.querySelector('.store-container').getBoundingClientRect().width,
      overflow: document.documentElement.scrollWidth > innerWidth,
    })))
  }
  console.log('pageErrors', errors)
} finally {
  await browser.close()
}

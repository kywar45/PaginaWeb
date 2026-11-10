import { chromium } from 'file:///C:/Users/Andru/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/.pnpm/playwright-core@1.60.0/node_modules/playwright-core/index.mjs'
import { fileURLToPath } from 'node:url'

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
})
try {
  const page = await browser.newPage({ viewport: { width: 1544, height: 1017 } })
  const errors = []
  page.on('pageerror', error => errors.push(error.message))
  await page.goto('http://localhost:9000/ImportadoraSupernova/', { waitUntil: 'networkidle' })
  await page.locator('.banner-loading').waitFor({ state: 'hidden', timeout: 20000 })
  await page.screenshot({ path: fileURLToPath(new URL('./palette-desktop.png', import.meta.url)) })
  console.log('desktop', await page.evaluate(() => ({
    primary: getComputedStyle(document.documentElement).getPropertyValue('--q-primary'),
    header: getComputedStyle(document.querySelector('.sn-header')).backgroundImage,
    button: getComputedStyle(document.querySelector('.offer-card .q-btn')).backgroundImage,
    icon: getComputedStyle(document.querySelector('.store-benefit > .q-icon')).color,
    overflow: document.documentElement.scrollWidth > innerWidth,
  })))
  await page.locator('.sn-all-category-trigger').click()
  await page.locator('.q-menu .catalog-menu').waitFor()
  await page.waitForFunction(() => getComputedStyle(document.querySelector('.q-menu')).opacity === '1')
  await page.locator('.q-menu .catalog-menu__campaign-photo').waitFor()
  await page.screenshot({ path: fileURLToPath(new URL('./palette-menu.png', import.meta.url)) })
  await page.keyboard.press('Escape')
  await page.setViewportSize({ width: 390, height: 844 })
  await page.screenshot({ path: fileURLToPath(new URL('./palette-mobile.png', import.meta.url)) })
  console.log('mobile', await page.evaluate(() => ({
    overflow: document.documentElement.scrollWidth > innerWidth,
    menuColor: getComputedStyle(document.querySelector('.sn-mobile-head__row > .q-btn')).color,
  })))
  console.log('pageErrors', errors)
} finally {
  await browser.close()
}

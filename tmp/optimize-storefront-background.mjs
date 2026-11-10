import sharp from 'file:///C:/Users/Andru/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/.pnpm/sharp@0.34.5/node_modules/sharp/lib/index.js'

const result = await sharp('tmp/storefront-background-source.png')
  .webp({ quality: 86, effort: 6 })
  .toFile('src/assets/storefront-background.webp')
console.log(result)

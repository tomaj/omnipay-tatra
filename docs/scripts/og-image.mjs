// Rasterizes public/og.svg and og-sk.svg to public/og.png and og-sk.png (1200 × 630) and public/logo.svg to the PNG icons and favicon.ico.
// Run after scripts/illustrations.py: node scripts/og-image.mjs (CHROME_PATH points at Chrome or Chromium).
import { readFileSync, writeFileSync } from 'node:fs'
import puppeteer from 'puppeteer-core'

const pub = (name) => new URL(`../public/${name}`, import.meta.url)
const chrome = process.env.CHROME_PATH
  || (process.platform === 'darwin' ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' : '/usr/bin/google-chrome')

const browser = await puppeteer.launch({ executablePath: chrome, args: ['--no-sandbox'] })
const page = await browser.newPage()

async function render(svgFile, width, height, transparent = false) {
  const svg = readFileSync(pub(svgFile), 'utf8').replace(/<svg([^>]*?) width="\d+" height="\d+"/, `<svg$1 width="${width}" height="${height}"`)
  await page.setViewport({ width, height })
  await page.setContent(`<html><body style="margin:0;background:${transparent ? 'transparent' : '#fff'}">${svg}</body></html>`)
  return page.screenshot({ omitBackground: transparent, clip: { x: 0, y: 0, width, height } })
}

writeFileSync(pub('og.png'), await render('og.svg', 1200, 630))
writeFileSync(pub('og-sk.png'), await render('og-sk.svg', 1200, 630))
const icons = {}
for (const [name, size] of [['favicon-16.png', 16], ['favicon-32.png', 32], ['icon-192.png', 192], ['icon-512.png', 512]]) {
  icons[size] = await render('logo.svg', size, size, true)
  writeFileSync(pub(name), icons[size])
}
// iOS ignores transparency: the mark on white
writeFileSync(pub('apple-touch-icon.png'), await render('logo.svg', 180, 180))
await browser.close()

// favicon.ico with the 16 and 32 pixel PNGs embedded (PNG-in-ICO)
const images = [icons[16], icons[32]]
const header = Buffer.alloc(6 + 16 * images.length)
header.writeUInt16LE(0, 0)
header.writeUInt16LE(1, 2)
header.writeUInt16LE(images.length, 4)
let offset = header.length
images.forEach((png, i) => {
  const size = i === 0 ? 16 : 32
  const entry = 6 + 16 * i
  header.writeUInt8(size, entry)
  header.writeUInt8(size, entry + 1)
  header.writeUInt16LE(1, entry + 4)
  header.writeUInt16LE(32, entry + 6)
  header.writeUInt32LE(png.length, entry + 8)
  header.writeUInt32LE(offset, entry + 12)
  offset += png.length
})
writeFileSync(pub('favicon.ico'), Buffer.concat([header, ...images]))
console.log('public/og.png, og-sk.png, the PNG icons and favicon.ico written')

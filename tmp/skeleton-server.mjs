import http from 'node:http'
import { readFile } from 'node:fs/promises'
import { resolve, extname } from 'node:path'
const root = resolve('dist/spa')
let releaseImages
let imagesReady = new Promise(resolve => { releaseImages = resolve })
const server = http.createServer(async (request, response) => {
  const url = new URL(request.url, 'http://127.0.0.1:4173')
  if (url.pathname === '/reset-images') { imagesReady = new Promise(resolve => { releaseImages = resolve }); response.end('ok'); return }
  if (url.pathname === '/release-images') { releaseImages(); response.end('ok'); return }
  if (url.pathname === '/slow.svg') {
    await imagesReady
    response.setHeader('Content-Type', 'image/svg+xml')
    response.setHeader('Cache-Control', 'no-store')
    response.end('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#fde6ef"/><circle cx="100" cy="100" r="45" fill="#f4456f"/></svg>')
    return
  }
  let file = resolve(root, decodeURIComponent(url.pathname.replace(/^\/ImportadoraSupernova\/?/, '')))
  if (!file.startsWith(root)) { response.writeHead(403); response.end(); return }
  const types = { '.js': 'application/javascript', '.css': 'text/css', '.svg': 'image/svg+xml', '.png': 'image/png', '.webp': 'image/webp', '.woff2': 'font/woff2', '.html': 'text/html' }
  try {
    const body = await readFile(file)
    response.setHeader('Content-Type', types[extname(file)] || 'application/octet-stream')
    response.end(body)
  } catch {
    response.setHeader('Content-Type', 'text/html')
    response.end(await readFile(resolve(root, 'index.html')))
  }
})
server.listen(4173, '127.0.0.1', () => console.log('Skeleton preview listening on 4173'))

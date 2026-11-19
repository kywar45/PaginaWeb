import assert from 'node:assert/strict'
import { isInspirationMedia } from '../../src/utils/inspirationMedia.js'

assert.ok(isInspirationMedia('https://www.importadorasupernova.com/videos/tutorial.mp4', 'video'))
assert.ok(
  isInspirationMedia('https://importadorasupernova.com:443/videos/TUTORIAL.MP4?v=2', 'video'),
)
for (const extension of ['jpg', 'jpeg', 'png', 'webp', 'avif']) {
  assert.ok(
    isInspirationMedia(`https://www.importadorasupernova.com/videos/poster.${extension}`, 'image'),
  )
}
for (const url of [
  null,
  '',
  'javascript:alert(1)',
  'http://www.importadorasupernova.com/video.mp4',
  'https://evil.test/video.mp4',
  'https://www.importadorasupernova.com.evil.test/video.mp4',
  'https://user:password@www.importadorasupernova.com/video.mp4',
  'https://www.importadorasupernova.com:444/video.mp4',
  'https://www.importadorasupernova.com/video.mp4.php',
  'https://www.importadorasupernova.com/video.mp4#fragment',
  'https://www.tiktok.com/@user/video/1234567890123456789',
]) {
  assert.equal(isInspirationMedia(url, 'video'), false)
}
assert.equal(isInspirationMedia('https://www.importadorasupernova.com/poster.php', 'image'), false)
console.log('Inspiration media validation passed.')

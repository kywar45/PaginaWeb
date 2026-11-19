export function isInspirationMedia(value, type) {
  if (
    typeof value !== 'string' ||
    [...value].some((char) => char.charCodeAt(0) <= 32 || char.charCodeAt(0) === 127)
  )
    return false
  try {
    const url = new URL(value)
    if (
      url.protocol !== 'https:' ||
      !['importadorasupernova.com', 'www.importadorasupernova.com'].includes(url.hostname) ||
      url.username ||
      url.password ||
      url.hash ||
      url.port
    )
      return false
    return (type === 'video' ? /\.mp4$/i : /\.(jpe?g|png|webp|avif)$/i).test(url.pathname)
  } catch {
    return false
  }
}

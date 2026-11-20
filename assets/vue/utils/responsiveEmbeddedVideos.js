const VIDEO_IFRAME_HOSTS = new Set([
  "youtube.com",
  "www.youtube.com",
  "youtube-nocookie.com",
  "www.youtube-nocookie.com",
  "player.vimeo.com",
])

function isVideoIframe(frame) {
  const source = frame.getAttribute("src")
  if (!source) return false

  try {
    const url = new URL(source, frame.ownerDocument.baseURI)
    const host = url.hostname.toLowerCase()
    if (!VIDEO_IFRAME_HOSTS.has(host)) return false

    return host === "player.vimeo.com" ? url.pathname.startsWith("/video/") : url.pathname.startsWith("/embed/")
  } catch {
    return false
  }
}

function pixelSize(value) {
  const size = String(value ?? "").trim()
  return /^\d+(?:\.\d+)?(?:px)?$/i.test(size) ? Number.parseFloat(size) : 0
}

function makeVideoResponsive(element, embeddedFrame) {
  const styles = element.style

  // Do not alter already-responsive media or absolutely positioned players.
  if (element.dataset.chamiloResponsiveVideo || styles.width === "100%" && styles.height === "auto") {
    return false
  }
  if (element.ownerDocument.defaultView?.getComputedStyle(element).position === "absolute") {
    return false
  }

  const width = pixelSize(element.getAttribute("width")) || pixelSize(styles.width)
  const height = pixelSize(element.getAttribute("height")) || pixelSize(styles.height)

  styles.setProperty("display", "block", "important")
  styles.setProperty("box-sizing", "border-box", "important")
  styles.setProperty("width", "100%", "important")
  styles.setProperty("max-width", width > 0 ? `min(100%, ${width}px)` : "100%", "important")
  styles.setProperty("height", "auto", "important")

  if (embeddedFrame) {
    styles.setProperty("aspect-ratio", width > 0 && height > 0 ? `${width} / ${height}` : "16 / 9", "important")
  }

  const parent = element.parentElement
  if (parent && (pixelSize(parent.style.width) > 0 || pixelSize(parent.getAttribute("width")) > 0)) {
    parent.style.setProperty("max-width", "100%", "important")
  }

  element.dataset.chamiloResponsiveVideo = "true"
  return true
}

// Only adapt media in readable HTML documents. External, sandboxed and SCORM frames are left untouched.
export function makeEmbeddedVideosResponsive(frame) {
  let frameDocument
  try {
    frameDocument = frame?.contentDocument
  } catch {
    return 0
  }

  if (!frameDocument || frameDocument.contentType !== "text/html") return 0

  let adapted = 0
  for (const media of frameDocument.querySelectorAll("iframe[src], video")) {
    const embeddedFrame = media.localName === "iframe"
    if (embeddedFrame && !isVideoIframe(media)) continue

    if (makeVideoResponsive(media, embeddedFrame)) adapted += 1
  }

  return adapted
}

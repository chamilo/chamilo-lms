function toAbsoluteUrl(raw) {
  const v = String(raw || "").trim()
  if (!v) return ""
  try {
    return new URL(v, window.location.origin).href
  } catch {
    return v
  }
}

function getQueryParam(name) {
  try {
    return new URLSearchParams(window.location.search).get(name) || ""
  } catch {
    return ""
  }
}

export function pickUrlForTinyMce(rawUrl, options = {}) {
  const url = toAbsoluteUrl(rawUrl)
  if (!url) return

  const { cbId = getQueryParam("cbId"), close = true, logPrefix = "[TINY PICKER]" } = options

  const payload = { mceAction: "fileSelected", content: { url } }

  // Direct callback registry
  try {
    const registry = window.parent?.__chamiloTinyPickerCallbacks
    if (cbId && registry && typeof registry[cbId] === "function") {
      registry[cbId](url)
      delete registry[cbId]
    }
  } catch (e) {
    console.warn(logPrefix, "Failed to call cbId registry callback", e)
  }

  // TinyMCE windowManager message (triggers BaseTinyEditor onMessage)
  try {
    window.parent?.tinymce?.activeEditor?.windowManager?.sendMessage(payload)
  } catch (e) {
    // Not fatal
  }

  // postMessage fallback (supports older bridge handlers)
  try {
    window.parent?.postMessage(payload, window.location.origin)
  } catch {
    // ignore
  }
  try {
    window.parent?.postMessage({ url }, "*")
  } catch {
    // ignore
  }

  // Close dialog if possible
  if (close) {
    try {
      window.parent?.tinymce?.activeEditor?.windowManager?.close()
    } catch {
      // ignore
    }
  }
}

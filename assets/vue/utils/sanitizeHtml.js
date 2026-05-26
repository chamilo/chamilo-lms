import DOMPurify from "dompurify"

/**
 * Sanitize untrusted HTML before injecting it into v-html.
 */
export function sanitizeHtml(dirtyHtml, options = {}) {
  return DOMPurify.sanitize(String(dirtyHtml ?? ""), {
    ADD_ATTR: ["target", "rel"],
    ...options,
  })
}

/**
 * Ensure target=_blank links are safe.
 */
let hooksRegistered = false

export function sanitizeHtmlWithSafeLinks(dirtyHtml, options = {}) {
  if (!hooksRegistered) {
    DOMPurify.addHook("afterSanitizeAttributes", (node) => {
      if (node.tagName === "A") {
        const target = node.getAttribute("target")
        if (target === "_blank") {
          const rel = (node.getAttribute("rel") || "").toLowerCase()
          const parts = rel.split(/\s+/).filter(Boolean)

          if (!parts.includes("noopener")) parts.push("noopener")
          if (!parts.includes("noreferrer")) parts.push("noreferrer")

          node.setAttribute("rel", parts.join(" "))
        }
      }
    })

    hooksRegistered = true
  }

  return sanitizeHtml(dirtyHtml, options)
}

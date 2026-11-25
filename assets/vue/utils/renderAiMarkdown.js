import DOMPurify from "dompurify"

export function escapeForHtml(s) {
  return (s || "").replace(
    /[&<>"']/g,
    (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[m],
  )
}

function safeHttpUrl(rawUrl) {
  try {
    const url = new URL(String(rawUrl || ""))
    if (!/^https?:$/i.test(url.protocol)) return ""
    return url.toString()
  } catch {
    return ""
  }
}

/**
 * Render the limited Markdown normally returned by AI providers.
 * Raw HTML is never trusted: content is escaped first and DOMPurify remains
 * the final safety boundary.
 */
function renderAiInlineMarkdown(rawText) {
  let text = String(rawText ?? "")

  // Providers occasionally escape Markdown punctuation (\*\*, \[, ...).
  // Unescape it only for assistant messages so normal user chat stays literal.
  text = text.replace(/\\([\\`*_[\]{}()#+\-.!>])/g, "$1")

  const tokens = []
  const token = (html) => {
    const key = `\uE000${tokens.length}\uE001`
    tokens.push(html)
    return key
  }

  // Markdown links first so the URL is not picked up again by bare-link logic.
  text = text.replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/g, (match, label, rawUrl) => {
    const href = safeHttpUrl(rawUrl)
    if (!href) return match

    return token(
      `<a href="${escapeForHtml(href)}" target="_blank" rel="noopener noreferrer">${escapeForHtml(label)}</a>`,
    )
  })

  // Inline code.
  text = text.replace(/`([^`\n]+)`/g, (match, code) => token(`<code>${escapeForHtml(code)}</code>`))

  // Bare HTTP(S) URLs.
  text = text.replace(/https?:\/\/[^\s<>"']+/g, (rawUrl) => {
    const href = safeHttpUrl(rawUrl)
    if (!href) return rawUrl

    return token(
      `<a href="${escapeForHtml(href)}" target="_blank" rel="noopener noreferrer">${escapeForHtml(rawUrl)}</a>`,
    )
  })

  let html = escapeForHtml(text)

  // Limited emphasis support. Keep this intentionally small and predictable.
  html = html.replace(/\*\*([^*\n]+)\*\*/g, "<strong>$1</strong>")
  html = html.replace(/__([^_\n]+)__/g, "<strong>$1</strong>")
  html = html.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, "$1<em>$2</em>")
  html = html.replace(/(^|[^_])_([^_\n]+)_(?!_)/g, "$1<em>$2</em>")

  tokens.forEach((value, index) => {
    html = html.replaceAll(`\uE000${index}\uE001`, value)
  })

  return html
}

export function renderAiMarkdown(rawMessage) {
  const text = String(rawMessage ?? "").replace(/\r\n?/g, "\n")
  const lines = text.split(/\n/)
  const out = []
  let listType = ""

  const closeList = () => {
    if (!listType) return
    out.push(`</${listType}>`)
    listType = ""
  }

  const openList = (type) => {
    if (listType === type) return
    closeList()
    listType = type
    out.push(`<${type}>`)
  }

  for (const rawLine of lines) {
    const line = rawLine.trimEnd()

    if (!line.trim()) {
      closeList()
      out.push("<br>")
      continue
    }

    const heading = line.match(/^#{1,6}\s+(.+)$/)
    if (heading) {
      closeList()
      out.push(`<strong>${renderAiInlineMarkdown(heading[1])}</strong><br>`)
      continue
    }

    if (/^\s*---+\s*$/.test(line)) {
      closeList()
      out.push("<hr>")
      continue
    }

    const bullet = line.match(/^\s*[-*]\s+(.+)$/)
    if (bullet) {
      openList("ul")
      out.push(`<li>${renderAiInlineMarkdown(bullet[1])}</li>`)
      continue
    }

    const numbered = line.match(/^\s*\d+[.)]\s+(.+)$/)
    if (numbered) {
      openList("ol")
      out.push(`<li>${renderAiInlineMarkdown(numbered[1])}</li>`)
      continue
    }

    closeList()
    out.push(`${renderAiInlineMarkdown(line)}<br>`)
  }

  closeList()

  return DOMPurify.sanitize(out.join(""), {
    ALLOWED_TAGS: ["a", "br", "strong", "em", "code", "ul", "ol", "li", "hr"],
    ALLOWED_ATTR: ["href", "target", "rel"],
    ADD_ATTR: ["target", "rel"],
  })
}

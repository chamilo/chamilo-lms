/* For licensing terms, see /license.txt */

/**
 * Global AI disclosure beautifier.
 * Replaces markers like "[AI-assisted]" or "🤖 AI-assisted" with a badge + tooltip.
 *
 * Safety goals:
 * - Do nothing if disclosure is disabled.
 * - Observe only small containers (#app and/or #sectionMainContent), not the full body.
 * - Avoid heavy work if markers are not present.
 * - Avoid touching editors/inputs/code blocks.
 * - Prevent double init and duplicate styles.
 */
;(() => {
  "use strict"

  // Prevent double init (some pages include bundles twice)
  if (window.__chAiDisclosureBadgeInit) return
  window.__chAiDisclosureBadgeInit = true

  const MARKERS = ["[AI-assisted]", "🤖 AI-assisted"]
  const TOOLTIP = "Co-generated with AI"

  // Optional runtime switch (set from backend)
  function isEnabled() {
    if (typeof window.CH_AI_DISCLOSURE_ENABLED === "boolean") {
      return window.CH_AI_DISCLOSURE_ENABLED
    }
    return true
  }

  function ensureStyle() {
    if (document.getElementById("ch-ai-disclosure-style")) return

    const style = document.createElement("style")
    style.id = "ch-ai-disclosure-style"
    style.type = "text/css"
    style.appendChild(
      document.createTextNode(
        ".ch-ai-badge{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;margin:0 8px 0 0;" +
          "border:1px solid #cbd5e1;border-radius:9999px;background:#f8fafc;color:#334155;" +
          "font-size:12px;line-height:16px;white-space:nowrap;vertical-align:middle;cursor:help}" +
          ".ch-ai-badge__icon{font-size:12px;line-height:12px}" +
          ".ch-ai-badge__label{font-weight:600;letter-spacing:.2px}",
      ),
    )
    document.head.appendChild(style)
  }

  function makeBadge() {
    const badge = document.createElement("span")
    badge.className = "ch-ai-badge"
    badge.title = TOOLTIP
    badge.setAttribute("aria-label", TOOLTIP)
    badge.setAttribute("role", "note")
    badge.tabIndex = 0

    const icon = document.createElement("span")
    icon.className = "ch-ai-badge__icon"
    icon.setAttribute("aria-hidden", "true")
    icon.textContent = "🤖"

    const label = document.createElement("span")
    label.className = "ch-ai-badge__label"
    label.textContent = "AI"

    badge.appendChild(icon)
    badge.appendChild(label)

    return badge
  }

  function isSkippableElement(el) {
    if (!el || el.nodeType !== 1) return true

    const tag = (el.tagName || "").toUpperCase()
    if (["SCRIPT", "STYLE", "TEXTAREA", "INPUT", "SELECT", "OPTION"].includes(tag)) return true

    // Avoid rich editors and code blocks
    if (el.closest && el.closest(".tox-tinymce, pre, code")) return true

    return false
  }

  function findMarkerInString(s) {
    if (!s) return null
    for (let i = 0; i < MARKERS.length; i++) {
      if (s.includes(MARKERS[i])) return MARKERS[i]
    }
    return null
  }

  function replaceInTextNode(node) {
    const text = node.nodeValue || ""
    const marker = findMarkerInString(text)
    if (!marker) return

    const parent = node.parentNode
    if (!parent || parent.nodeType !== 1) return
    if (isSkippableElement(parent)) return

    const idx = text.indexOf(marker)
    const before = text.slice(0, idx)
    let after = text.slice(idx + marker.length)

    // Clean extra spaces right after marker (common case)
    after = after.replace(/^\s+/, " ")

    parent.insertBefore(document.createTextNode(before), node)
    parent.insertBefore(makeBadge(), node)
    parent.insertBefore(document.createTextNode(after), node)
    parent.removeChild(node)
  }

  function decorate(root) {
    if (!root) return

    // Quick skip: if root's text doesn't contain markers, don't walk it
    const rootText = root.textContent || ""
    if (!findMarkerInString(rootText)) return

    ensureStyle()

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: (n) => {
        const v = n.nodeValue || ""
        return findMarkerInString(v) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
      },
    })

    const nodes = []
    while (walker.nextNode()) nodes.push(walker.currentNode)

    for (let i = 0; i < nodes.length; i++) replaceInTextNode(nodes[i])
  }

  function getTargets() {
    // IMPORTANT: On many pages both exist.
    // Vue renders in #app, legacy renders in #sectionMainContent.
    const targets = []
    const app = document.getElementById("app")
    const legacy = document.getElementById("sectionMainContent")

    if (app) targets.push(app)
    if (legacy) targets.push(legacy)

    // Fallback: if none exist
    if (!targets.length && document.body) targets.push(document.body)

    return targets
  }

  // Batch work to avoid doing multiple scans in the same tick
  const pending = new Set()
  let rafId = 0

  function scheduleDecorate(root) {
    if (!root) return
    pending.add(root)

    if (rafId) return
    rafId = requestAnimationFrame(() => {
      rafId = 0
      pending.forEach((r) => decorate(r))
      pending.clear()
    })
  }

  let observer = null

  function installObserver(targets) {
    if (observer) return

    observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === "childList") {
          for (const n of m.addedNodes) {
            if (!n) continue

            if (n.nodeType === 1) {
              // Fast skip if subtree has no markers
              const t = n.textContent || ""
              if (!findMarkerInString(t)) continue
              scheduleDecorate(n)
            } else if (n.nodeType === 3) {
              // Text node added
              const v = n.nodeValue || ""
              if (!findMarkerInString(v)) continue
              scheduleDecorate(n.parentNode)
            }
          }
        } else if (m.type === "characterData") {
          // Vue can update existing text nodes without adding elements
          const v = m.target && m.target.nodeValue ? m.target.nodeValue : ""
          if (!findMarkerInString(v)) continue
          scheduleDecorate(m.target && m.target.parentNode ? m.target.parentNode : null)
        }
      }
    })

    // Observe all relevant containers
    for (let i = 0; i < targets.length; i++) {
      const t = targets[i]
      if (!t) continue
      observer.observe(t, { childList: true, subtree: true, characterData: true })
    }
  }

  function boot() {
    if (!isEnabled()) return

    const targets = getTargets()

    // First pass (cheap due to quick skip)
    for (let i = 0; i < targets.length; i++) {
      scheduleDecorate(targets[i])
    }

    // Needed for Vue pages: content arrives after mount
    installObserver(targets)
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot)
  } else {
    boot()
  }
})()

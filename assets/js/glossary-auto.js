/* For licensing terms, see /license.txt */

;(function () {
  "use strict"

  console.log("[Glossary] glossary_auto.js bundle loaded")

  function getConfig() {
    const cfg = window.chamiloGlossaryConfig || {}
    console.log("[Glossary] Loaded with config:", cfg)
    return cfg
  }

  function escapeRegExp(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
  }

  /**
   * Normalize ApiPlatform/Chamilo glossary payload into:
   *   [{ term: "AFP", definition: "..." }, ...]
   */
  function normalizeTermsPayload(data) {
    let items

    if (Array.isArray(data)) {
      items = data
    } else if (data && Array.isArray(data["hydra:member"])) {
      items = data["hydra:member"]
    } else if (data && Array.isArray(data.items)) {
      items = data.items
    } else {
      console.warn("[Glossary] Unknown payload format, no terms extracted")
      return []
    }

    const terms = items
      .map((item) => {
        if (!item) {
          return null
        }

        const term = item.term || item.title || item.name || null

        const definition = item.definition || item.description || ""

        if (!term) {
          return null
        }

        return {
          term: String(term),
          definition: String(definition || ""),
        }
      })
      .filter(Boolean)

    console.log("[Glossary] Normalized terms:", terms)

    return terms
  }

  async function fetchTerms() {
    const cfg = getConfig()

    if (!cfg.termsEndpoint) {
      console.warn("[Glossary] No termsEndpoint configured in chamiloGlossaryConfig")
      return []
    }

    if (!cfg.resourceNodeParentId) {
      console.warn("[Glossary] Missing resourceNodeParentId; skipping glossary fetch", cfg)
      return []
    }

    const params = new URLSearchParams()

    params.append("resourceNode.parent", cfg.resourceNodeParentId)

    if (cfg.courseId) {
      params.append("cid", cfg.courseId)
    }

    if (cfg.sessionId) {
      params.append("sid", cfg.sessionId)
    }

    const url = cfg.termsEndpoint + (cfg.termsEndpoint.includes("?") ? "&" : "?") + params.toString()

    try {
      const response = await fetch(url, {
        credentials: "same-origin",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      })

      if (!response.ok) {
        console.error("[Glossary] Failed to fetch terms, status:", response.status)
        return []
      }

      const raw = await response.json()
      return normalizeTermsPayload(raw)
    } catch (error) {
      console.error("[Glossary] Error while fetching terms", error)
      return []
    }
  }

  function getTextNodesUnder(root) {
    const walker = document.createTreeWalker(
      root,
      NodeFilter.SHOW_TEXT,
      {
        acceptNode(node) {
          if (!node || !node.nodeValue || !node.nodeValue.trim()) {
            return NodeFilter.FILTER_REJECT
          }

          const parent = node.parentNode
          if (!parent) {
            return NodeFilter.FILTER_REJECT
          }

          if (
            parent.closest &&
            parent.closest(".glossary-term, a, button, input, select, textarea, script, style, noscript, iframe, svg")
          ) {
            return NodeFilter.FILTER_REJECT
          }

          return NodeFilter.FILTER_ACCEPT
        },
      },
      false,
    )

    const nodes = []
    let current
    while ((current = walker.nextNode())) {
      nodes.push(current)
    }
    return nodes
  }

  function applyGlossary(terms) {
    if (!terms || !terms.length) {
      console.log("[Glossary] No terms to apply")
      return
    }

    const map = new Map()
    const patternParts = []

    terms.forEach((item) => {
      if (!item || !item.term) {
        return
      }

      const term = String(item.term).trim()
      if (term.length < 2) {
        return
      }

      const normalized = term.toLowerCase()
      if (!map.has(normalized)) {
        patternParts.push(term)
      }
      map.set(normalized, {
        term,
        definition: item.definition || "",
      })
    })

    if (!patternParts.length) {
      console.log("[Glossary] No valid terms after normalization")
      return
    }

    // Unicode-aware boundaries also match terms beginning or ending with accented letters.
    // Match longer terms first when glossary entries overlap.
    const pattern = patternParts
      .sort((a, b) => b.length - a.length)
      .map(escapeRegExp)
      .join("|")
    const regex = new RegExp(`(^|[^\\p{L}\\p{N}_])(${pattern})(?=$|[^\\p{L}\\p{N}_])`, "giu")

    console.log("[Glossary] Applying glossary with pattern:", regex)

    const textNodes = getTextNodesUnder(document.body)
    textNodes.forEach((node) => {
      const text = node.nodeValue
      if (!regex.test(text)) {
        regex.lastIndex = 0
        return
      }

      regex.lastIndex = 0
      const fragment = document.createDocumentFragment()
      let lastIndex = 0
      let match

      while ((match = regex.exec(text)) !== null) {
        const leadingText = match[1] || ""
        const matchText = match[2] || ""
        const index = match.index + leadingText.length

        if (index > lastIndex) {
          fragment.appendChild(document.createTextNode(text.slice(lastIndex, index)))
        }

        const normalized = matchText.toLowerCase()
        const info = map.get(normalized)

        const span = document.createElement("span")
        span.className = "glossary-term"
        span.textContent = matchText

        if (info) {
          span.dataset.glossaryTerm = info.term
          if (info.definition) {
            span.setAttribute("title", info.definition)
          }
        }

        fragment.appendChild(span)
        lastIndex = index + matchText.length
      }

      if (lastIndex < text.length) {
        fragment.appendChild(document.createTextNode(text.slice(lastIndex)))
      }

      if (node.parentNode) {
        node.parentNode.replaceChild(fragment, node)
      }
    })

    const jquery = window.jQuery
    if (jquery && jquery.fn && typeof jquery.fn.qtip === "function") {
      jquery(".glossary-term[title]").qtip({
        content: {
          attr: "title",
        },
        style: {
          classes: "qtip-light qtip-shadow",
        },
        position: {
          my: "top center",
          at: "bottom center",
        },
      })
    }
  }

  async function initGlossary() {
    console.log("[Glossary] initGlossary() called")

    const cfg = getConfig()
    if (!cfg.termsEndpoint) {
      console.warn("[Glossary] termsEndpoint not set, glossary auto-link disabled")
      return
    }

    try {
      const terms = await fetchTerms()
      if (!terms.length) {
        console.log("[Glossary] No terms returned from API")
        return
      }

      applyGlossary(terms)
    } catch (e) {
      console.error("[Glossary] Unexpected error in initGlossary", e)
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGlossary)
  } else {
    initGlossary()
  }
})()

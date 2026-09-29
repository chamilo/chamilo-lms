/* For licensing terms, see /license.txt */

// ──────────────────────────────────────────────────────────────────────────────
// MathJax – page-wide renderer for stored LaTeX (public/libs/editor/tinymce_plugins/mathjax)
// ──────────────────────────────────────────────────────────────────────────────
//
// The "mathjax" TinyMCE plugin stores a formula as:
//   <span class="math-latex" data-latex="ESCAPED_LATEX_SOURCE">...</span>
// It never inserts a <script> tag into saved content (that would require
// loosening the editor's schema for every rich-text field on the platform —
// a stored-XSS risk). Instead, this single bundle — loaded once from
// MathJax/mathjax_render.html.twig, and only when the enabled_mathjax setting
// is on — finds those spans wherever the stored content ends up displayed
// (legacy pages, Vue views) and typesets them.
//
// tex-svg.js itself (~1.8MB) is only fetched the first time a `.math-latex`
// node actually shows up on the page, not on every page load.
;(function () {
  "use strict"

  var SELECTOR = ".math-latex[data-latex]"
  var SCAN_DELAY = 100
  var mathJaxLoadingPromise = null
  var scanScheduled = false

  window.MathJax = window.MathJax || {
    // MathJax 4 ships its fonts as a separate package and defaults to
    // cdn.jsdelivr.net. Webpack copies the font next to the library, so point
    // the loader at that copy: an offline install must keep working, and no
    // page should call a third-party host.
    loader: {
      paths: {
        fonts: "/build/libs/mathjax-fonts",
      },
    },
    tex: {
      inlineMath: [["\\(", "\\)"]],
      displayMath: [
        ["\\[", "\\]"],
        ["$$", "$$"],
      ],
    },
    options: {
      skipHtmlTags: ["script", "noscript", "style", "textarea", "pre", "code"],
    },
    startup: {
      typeset: false,
    },
  }

  /**
   * Loads the MathJax library on first use and resolves once it is usable.
   * Every later call reuses the same promise, so the 1.8MB bundle is requested
   * only once per page.
   *
   * @returns {Promise<void>}
   */
  function ensureMathJaxLoaded() {
    if (mathJaxLoadingPromise) {
      return mathJaxLoadingPromise
    }

    mathJaxLoadingPromise = new Promise(function (resolve) {
      var existing = document.getElementById("mathjax-script")

      if (existing) {
        if (window.MathJax && typeof window.MathJax.typesetPromise === "function") {
          resolve()
        } else {
          existing.addEventListener("load", resolve)
        }
        return
      }

      var script = document.createElement("script")
      script.id = "mathjax-script"
      script.src = "/build/libs/mathjax/tex-svg.js"
      script.async = true
      script.onload = resolve
      document.head.appendChild(script)
    })

    return mathJaxLoadingPromise
  }

  /**
   * Writes each node's stored LaTeX back as text, then asks MathJax to typeset
   * the whole batch.
   *
   * @param {Element[]} nodes Unprocessed `.math-latex` elements.
   * @returns {void}
   */
  function typeset(nodes) {
    // `data-latex` is HTML-escaped by the plugin when written; getAttribute()
    // decodes it back to plain text, so this must stay textContent, never
    // innerHTML, or a formula containing markup would execute as HTML.
    nodes.forEach(function (node) {
      node.textContent = "\\(\\displaystyle " + node.getAttribute("data-latex") + "\\)"
    })

    ensureMathJaxLoaded()
      .then(function () {
        return window.MathJax.typesetPromise(nodes)
      })
      .then(function () {
        nodes.forEach(function (node) {
          node.classList.add("mathjax-processed")
        })
      })
      .catch(function (err) {
        console.error("MathJax typeset error:", err)
      })
  }

  /**
   * Collects every formula that is not typeset yet under `root` and renders it.
   *
   * @param {Document|Element} [root] Defaults to the whole document.
   * @returns {void}
   */
  function scan(root) {
    var nodes = Array.prototype.slice.call((root || document).querySelectorAll(SELECTOR)).filter(function (node) {
      return !node.classList.contains("mathjax-processed")
    })

    if (nodes.length) {
      typeset(nodes)
    }
  }

  /**
   * Runs one document scan per burst of DOM changes.
   *
   * Vue re-renders and legacy AJAX swaps both insert `.math-latex` nodes
   * without a page load, so the page still has to be watched. Checking each
   * inserted node would mean one CSS query per DOM insertion, which is a heavy
   * price on a SPA where most pages carry no formula at all. Batching turns
   * that into a single query per burst.
   *
   * @returns {void}
   */
  function scheduleScan() {
    if (scanScheduled) {
      return
    }

    scanScheduled = true

    window.setTimeout(function () {
      scanScheduled = false
      scan(document)
    }, SCAN_DELAY)
  }

  /**
   * Renders the formulas already in the page, then watches for new ones.
   *
   * @returns {void}
   */
  function start() {
    scan(document)

    new MutationObserver(scheduleScan).observe(document.body, {
      childList: true,
      subtree: true,
    })
  }

  if ("loading" === document.readyState) {
    document.addEventListener("DOMContentLoaded", start)
  } else {
    start()
  }

  // Exposed so the "mathjax" TinyMCE plugin can eagerly start loading MathJax
  // in the main document the moment its formula dialog opens — at that point
  // there is usually no `.math-latex` node on the page yet (new formula,
  // nothing inserted), so the observer above has nothing to react to and would
  // otherwise never trigger the load, leaving the dialog's live preview
  // without a working `MathJax.typesetPromise`.
  window.__chamiloEnsureMathJaxLoaded = ensureMathJaxLoaded
})()

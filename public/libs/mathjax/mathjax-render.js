// ──────────────────────────────────────────────────────────────────────────────
// MathJax – page-wide renderer for stored LaTeX (public/libs/editor/tinymce_plugins/latex)
// ──────────────────────────────────────────────────────────────────────────────
//
// The "latex" TinyMCE plugin stores a formula as:
//   <span class="math-latex" data-latex="ESCAPED_LATEX_SOURCE">...</span>
// It never inserts a <script> tag into saved content (that would require
// loosening the editor's schema for every rich-text field on the platform —
// a stored-XSS risk). Instead, this single script — loaded once from the
// shared page shell (src/CoreBundle/Resources/views/Layout/head.html.twig) —
// finds those spans wherever the stored content ends up displayed (legacy
// pages, Vue views) and typesets them.
//
// tex-svg.js itself (~2MB) is only fetched the first time a `.math-latex`
// node actually shows up on the page, not on every page load.
;(function () {
  "use strict"

  var SELECTOR = ".math-latex[data-latex]"
  var mathJaxLoadingPromise = null

  window.MathJax = window.MathJax || {
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
      script.src = "/libs/mathjax/tex-svg.js"
      script.async = true
      script.onload = resolve
      document.head.appendChild(script)
    })

    return mathJaxLoadingPromise
  }

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

  function scan(root) {
    var nodes = Array.prototype.slice
      .call((root || document).querySelectorAll(SELECTOR))
      .filter(function (node) {
        return !node.classList.contains("mathjax-processed")
      })

    if (nodes.length) {
      typeset(nodes)
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    scan(document)
  })

  // Vue re-renders and legacy AJAX content swaps both insert new
  // `.math-latex` nodes without a full page load — watch for them.
  var observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType !== 1) {
          return
        }

        if (node.matches && node.matches(SELECTOR)) {
          scan(node.parentNode || document)
        } else if (node.querySelector && node.querySelector(SELECTOR)) {
          scan(node)
        }
      })
    })
  })

  observer.observe(document.documentElement, { childList: true, subtree: true })

  // Exposed so the "latex" TinyMCE plugin can eagerly start loading MathJax
  // in the main document the moment its "Insert Math Equation" dialog opens —
  // at that point there's usually no `.math-latex` node on the page yet (new
  // formula, nothing inserted), so the MutationObserver above has nothing to
  // react to and would otherwise never trigger the load, leaving the dialog's
  // live preview without a working `MathJax.typesetPromise`.
  window.__chamiloEnsureMathJaxLoaded = ensureMathJaxLoaded
})()

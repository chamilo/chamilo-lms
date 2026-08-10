/* For licensing terms, see /license.txt */

import $ from "jquery"
import "jquery-ui/dist/jquery-ui"

window.jQuery = $
window.$ = $
global.jQuery = $

const frameReady = require("/public/main/inc/lib/javascript/jquery.frameready.js")

global.frameReady = frameReady
window.frameReady = frameReady

// Lean build: TinyMCE's codesample plugin auto-detects a global "hljs" and uses it
// for highlighting, but only offers its own default language dropdown (mapped below)
// unless codesample_languages is overridden — the full "highlight.js" package bundles
// all ~190 languages, most of which are unreachable from that dropdown.
var hljs = require("highlight.js/lib/core")
hljs.registerLanguage("markup", require("highlight.js/lib/languages/xml"))
hljs.registerLanguage("javascript", require("highlight.js/lib/languages/javascript"))
hljs.registerLanguage("css", require("highlight.js/lib/languages/css"))
hljs.registerLanguage("php", require("highlight.js/lib/languages/php"))
hljs.registerLanguage("ruby", require("highlight.js/lib/languages/ruby"))
hljs.registerLanguage("python", require("highlight.js/lib/languages/python"))
hljs.registerLanguage("java", require("highlight.js/lib/languages/java"))
hljs.registerLanguage("c", require("highlight.js/lib/languages/c"))
hljs.registerLanguage("csharp", require("highlight.js/lib/languages/csharp"))
hljs.registerLanguage("cpp", require("highlight.js/lib/languages/cpp"))
global.hljs = hljs

document.addEventListener("DOMContentLoaded", (event) => {
  var tabLinks = document.querySelectorAll(".nav-item.nav-link")

  function removeActiveClasses() {
    tabLinks.forEach(function (link) {
      link.classList.remove("active")
      var tabPanel = document.getElementById(link.getAttribute("aria-controls"))
      if (tabPanel) {
        tabPanel.classList.remove("active")
      }
    })
  }

  tabLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      removeActiveClasses()
      this.classList.add("active")
      var tabContentId = this.getAttribute("aria-controls")
      var tabContent = document.getElementById(tabContentId)
      if (tabContent) {
        tabContent.classList.add("active")
      }
    })
  })

  document
    .querySelectorAll(".accordion")
    .forEach((accordion) => accordion.addEventListener("click", () => accordion.classList.toggle("active")))
})

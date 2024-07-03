/* For licensing terms, see /license.txt */

import $ from "jquery"
import "jquery-ui-dist/jquery-ui.js"

window.jQuery = $
window.$ = $
global.jQuery = $

const frameReady = require("/public/main/inc/lib/javascript/jquery.frameready.js")

global.frameReady = frameReady
window.frameReady = frameReady

var hljs = require("highlight.js")
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

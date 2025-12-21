/* For licensing terms, see /license.txt */

const $ = require("jquery")

window.jQuery = $
window.$ = $
global.jQuery = $
global.$ = global.jQuery = $

//Routing.setRoutingData(routes);

const locale = document.documentElement.dataset.lang
// moment
const { DateTime } = require("luxon")
window.luxon = global.luxon = DateTime
import "select2/dist/js/select2.full.min"
import "select2/dist/css/select2.min.css"
import "moment"
//require('flatpickr');
import "jquery-ui/dist/jquery-ui"
import "./main"

// Date time settings.
import moment from "moment"
import Sortable from "sortablejs"
import Swal from "sweetalert2"
import "./vendor"

// Gets HTML content from tinymce
window.getContentFromEditor = function (id) {
  if (typeof tinymce == "undefined") {
    return false
  }

  let content = ""
  if (tinymce.get(id)) {
    content = tinymce.get(id).getContent()
  }

  return content
}

window.setContentFromEditor = function (id, content) {
  if (tinymce.get(id)) {
    tinymce.get(id).setContent(content)
    return true
  }

  return false
}

// const frameReady = require('/public/main/inc/lib/javascript/jquery.frameready.js');
//
// global.frameReady = frameReady;
// window.frameReady = frameReady;

global.moment = moment
moment.locale(locale)
//$.datepicker.setDefaults($.datepicker.regional[locale]);
//$.datepicker.regional["local"] = $.datepicker.regional[locale];

import("qtip2")
require("bootstrap-daterangepicker/daterangepicker.js")

require("blueimp-file-upload")
require("blueimp-load-image")
require("multiselect-two-sides")
require("datepair.js")
require("timepicker")

//import 'jquery-sortablejs';

window.Sortable = Sortable

window.Swal = Swal

// @todo rework url naming
//const homePublicUrl = Routing.generate('index');
const homePublicUrl = "/"
const mainUrl = homePublicUrl + "main/"
const webAjax = homePublicUrl + "main/inc/ajax/"

$(function () {
  let courseId = $("body").attr("data-course-id")
  let webCidReq = "&cid=" + courseId + "&sid=" + $("body").attr("data-session-id")
  window.webCidReq = webCidReq

  $("#menu_courses").click(function () {
    return false
  })
  $("#menu_social").click(function () {
    return false
  })
  $("#menu_administrator").click(function () {
    return false
  })

  if (courseId > 0) {
    let courseCode = $("body").data("course-code")
    let logOutUrl = webAjax + "course.ajax.php?a=course_logout&cidReq=" + courseCode

    function courseLogout() {
      $.ajax({
        async: false,
        url: logOutUrl,
        success: function () {
          return 1
        },
      })
    }

    addMainEvent(window, "unload", courseLogout, false)
  }

  $("#open-view-list").click(function () {
    $("#student-list-work").fadeIn(300)
  })
  $("#closed-view-list").click(function () {
    $("#student-list-work").fadeOut(300)
  })

  // Removes the yellow input in Chrome
  if (navigator.userAgent.toLowerCase().indexOf("chrome") >= 0) {
    $(window).on("load", function () {
      $("input:-webkit-autofill").each(function () {
        var text = $(this).val()
        var name = $(this).attr("name")
        $(this).after(this.outerHTML).remove()
        $("input[name=" + name + "]").val(text)
      })
    })
  }

  // MODAL DELETE CONFIRM
  $(document).on("click", ".delete-swal", function (e) {
    e.preventDefault()

    const $a = $(this)
    const url = $a.attr("href")
    const title = $a.data("title") || $a.attr("title") || ""

    const confirmText = $a.data("confirm-text") || "Yes"
    const cancelText = $a.data("cancel-text") || "No"

    Swal.fire({
      title,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url
      }
    })
  })

  // Start modals
  // class='ajax' loads a page in a modal
  $("body").on("click", "a.ajax", function (e) {
    e.preventDefault()

    var contentUrl = this.href,
      loadModalContent = $.get(contentUrl),
      self = $(this)

    $.when(loadModalContent).done(function (modalContent) {
      var modalTitle = self.data("title") || " ",
        globalModalTitle = $("#global-modal").find("#global-modal-title"),
        globalModalBody = $("#global-modal").find("#global-modal-body")

      globalModalTitle.text(modalTitle)
      globalModalBody.html(modalContent)

      globalModalBody.css({ "max-height": "500px", overflow: "auto" })
      toggleModal("global-modal")
    })
  })

  $("#global-modal").on("hidden.bs.modal", function () {
    $(".embed-responsive").find("iframe").remove()
  })

  $("#close-global-model").on("click", function () {
    toggleModal("global-modal")
  })

  // Expands an image modal
  $("a.expand-image").on("click", function (e) {
    e.preventDefault()
    var title = $(this).attr("title")
    var image = new Image()
    image.onload = function () {
      if (title) {
        $("#expand-image-modal").find(".modal-title").text(title)
      } else {
        $("#expand-image-modal").find(".modal-title").html("&nbsp;")
      }

      $("#expand-image-modal").find(".modal-body").html(image)
      $("#expand-image-modal").modal({
        show: true,
      })
    }
    image.src = this.href
  })

  // Delete modal
  $("#confirm-delete").on("show.bs.modal", function (e) {
    $(this).find(".btn-ok").attr("href", $(e.relatedTarget).data("href"))
    //var message = '{{ 'AreYouSureToDeleteJS' | get_lang }}: <strong>' + $(e.relatedTarget).data('item-title') + '</strong>';
    var message = "AreYouSureToDeleteJS : <strong>" + $(e.relatedTarget).data("item-title") + "</strong>"

    if ($(e.relatedTarget).data("item-question")) {
      message = $(e.relatedTarget).data("item-question")
    }

    $(".debug-url").html(message)
  })
  // End modals

  // old jquery.menu.js
  $("#navigation a").stop().animate(
    {
      marginLeft: "50px",
    },
    1000,
  )

  $("#navigation div").hover(
    function () {
      $("a", $(this)).stop().animate(
        {
          marginLeft: "1px",
        },
        200,
      )
    },
    function () {
      $("a", $(this)).stop().animate(
        {
          marginLeft: "50px",
        },
        200,
      )
    },
  )

  jQuery.fn.filterByText = function (textbox) {
    return this.each(function () {
      var select = this
      var options = []
      $(select)
        .find("option")
        .each(function () {
          options.push({ value: $(this).val(), text: $(this).text() })
        })
      $(select).data("options", options)

      $(textbox).bind("change keyup", function () {
        var options = $(select).empty().data("options")
        var search = $.trim($(this).val())
        var regex = new RegExp(search, "gi")

        $.each(options, function (i) {
          var option = options[i]
          if (option.text.match(regex) !== null) {
            $(select).append($("<option>").text(option.text).val(option.value))
          }
        })
      })
    })
  }

  $(".black-shadow")
    .mouseenter(function () {
      $(this).addClass("hovered-course")
    })
    .mouseleave(function () {
      $(this).removeClass("hovered-course")
    })

  $("[data-toggle=popover]").each(function (i, obj) {
    $(this).popover({
      html: true,
      content: function () {
        var id = $(this).attr("id")

        return $("#popover-content-" + id).html()
      },
    })
  })

  /**
   * Advanced options
   * Usage
   * <a id="link" href="url">Advanced</a>
   * <div id="link_options">
   *     hidden content :)
   * </div>
   * */
  $(".advanced_options").on("click", function (event) {
    event.preventDefault()
    var id = $(this).attr("id") + "_options"

    $("#" + id).toggle()
    if ($("#card-container").height() > 700) {
      $("#card-container").css("height", "auto")
    } else {
      $("#card-container").css("height", "100vh")
    }

    if ($("#column-left").hasClass("col-md-12")) {
      $("#column-left").removeClass("col-md-12")
      $("#column-right").removeClass("col-md-12")
      $("#column-right").addClass("col-md-4")
      $("#column-left").addClass("col-md-8")
    } else {
      $("#column-left").removeClass("col-md-8")
      $("#column-right").removeClass("col-md-4")
      $("#column-left").addClass("col-md-12")
      $("#column-right").addClass("col-md-12")
    }
    if ($("#preview_course_add_course").length >= 0) {
      $("#preview_course_add_course").toggle()
    }
  })

  /**
   * <a class="advanced_options_open" href="http://" rel="div_id">Open</a>
   * <a class="advanced_options_close" href="http://" rel="div_id">Close</a>
   * <div id="div_id">Div content</div>
   * */
  $(".advanced_options_open").on("click", function (event) {
    event.preventDefault()
    var id = $(this).attr("rel")
    $("#" + id).show()
  })

  $(".advanced_options_close").on("click", function (event) {
    event.preventDefault()
    var id = $(this).attr("rel")
    $("#" + id).hide()
  })

  // Adv multi-select search input.
  $(".select_class_filter").each(function () {
    var inputId = $(this).attr("id")
    inputId = inputId.replace("-filter", "")
    $("#" + inputId).filterByText($("#" + inputId + "-filter"))
  })

  // Table highlight.
  $("form .data_table input:checkbox").click(function () {
    if ($(this).is(":checked")) {
      $(this).parentsUntil("tr").parent().addClass("row_selected")
    } else {
      $(this).parentsUntil("tr").parent().removeClass("row_selected")
    }
  })

  // Tool tip (in exercises)
  var tip_options = {
    placement: "right",
  }
  //$('.boot-tooltip').tooltip(tip_options);
})

$(document).scroll(function () {
  var valor = $("body").outerHeight() - 700
  if ($(this).scrollTop() > 100) {
    $(".bottom_actions").addClass("bottom_actions_fixed")
  } else {
    $(".bottom_actions").removeClass("bottom_actions_fixed")
  }

  if ($(this).scrollTop() > valor) {
    $(".bottom_actions").removeClass("bottom_actions_fixed")
  } else {
    $(".bottom_actions").addClass("bottom_actions_fixed")
  }

  // Exercise warning fixed at the top.
  var fixed = $("#exercise_clock_warning")
  if (fixed.length) {
    if (!fixed.attr("data-top")) {
      // If already fixed, then do nothing
      if (fixed.hasClass("subnav-fixed")) return
      // Remember top position
      var offset = fixed.offset()
      fixed.attr("data-top", offset.top)
      fixed.css("width", "100%")
    }

    if (fixed.attr("data-top") - fixed.outerHeight() <= $(this).scrollTop()) {
      fixed.addClass("navbar-fixed-top")
      fixed.css("width", "100%")
    } else {
      fixed.removeClass("navbar-fixed-top")
      fixed.css("width", "100%")
    }
  }

  // Admin -> Settings toolbar.
  if ($("body").width() > 959) {
    if ($(".new_actions").length) {
      if (!$(".new_actions").attr("data-top")) {
        // If already fixed, then do nothing
        if ($(".new_actions").hasClass("new_actions-fixed")) return
        // Remember top position
        var offset = $(".new_actions").offset()

        var more_top = 0
        if ($(".subnav").hasClass("new_actions-fixed")) {
          more_top = 50
        }
        $(".new_actions").attr("data-top", offset.top + more_top)
      }
      // Check if the height is enough before fixing the icons menu (or otherwise removing it)
      // Added a 30px offset otherwise sometimes the menu plays ping-pong when scrolling to
      // the bottom of the page on short pages.
      if ($(".new_actions").attr("data-top") - $(".new_actions").outerHeight() <= $(this).scrollTop() + 30) {
        $(".new_actions").addClass("new_actions-fixed")
      } else {
        $(".new_actions").removeClass("new_actions-fixed")
      }
    }
  }
})

// focus first meaningful field + Enter=submit (any form, any container)
;(function () {
  // Avoid double-install
  if (window.__A11Y_INSTALLED__) {
    return
  }
  window.__A11Y_INSTALLED__ = true

  const NS = "[A11Y]"
  const boundForms = new WeakSet()
  const TEXT_TYPES = new Set([
    "text",
    "email",
    "password",
    "search",
    "url",
    "tel",
    "number",
    "date",
    "datetime-local",
    "month",
    "time",
    "week",
    "color",
  ])

  const isVisible = (el) => {
    if (!el) return false
    const s = getComputedStyle(el)
    if (s.visibility === "hidden" || s.display === "none") return false
    const r = el.getBoundingClientRect()
    return r.width > 0 && r.height > 0
  }

  const inViewport = (el) => {
    if (!el) return false
    const r = el.getBoundingClientRect()
    const h = window.innerHeight || document.documentElement.clientHeight
    return r.top < h && r.bottom > 0
  }

  function listFocusable(root) {
    const nodes = Array.from(
      root.querySelectorAll(
        [
          'input:not([type="hidden"]):not([disabled])',
          "textarea:not([disabled])",
          "select:not([disabled])",
          '[contenteditable="true"]',
        ].join(","),
      ),
    )
    return nodes.filter((el) => {
      if (!isVisible(el)) return false
      if (el.tagName === "INPUT") {
        const type = (el.getAttribute("type") || "text").toLowerCase()
        if (!TEXT_TYPES.has(type)) return false
        if (el.readOnly) return false
      }
      return true
    })
  }

  function pickFocusTarget(form) {
    // explicit markers
    const explicit = form.querySelector("[autofocus], [data-autofocus]")
    if (explicit && isVisible(explicit)) return explicit

    // 'title' or 'name'
    const all = listFocusable(form)
    const match = all.find((el) => {
      const id = (el.id || "").toLowerCase()
      const name = (el.name || "").toLowerCase()
      return id.includes("title") || name.includes("title") || id === "name" || name === "name"
    })
    return match || all[0] || null
  }

  function focusWithRetries(el, attempt = 0) {
    if (!el || !isVisible(el)) {
      if (attempt === 0) console.log(NS, "No visible element to focus.")
      return
    }

    // If Select2 hid the <select>, focus the visible selection
    if (el.classList.contains("select2-hidden-accessible")) {
      const s2 = el.nextElementSibling && el.nextElementSibling.querySelector(".select2-selection")
      if (s2) el = s2
    }

    el.focus({ preventScroll: false })
    const ok = document.activeElement === el
    console.log(NS, `Focus attempt #${attempt + 1}:`, ok ? "OK" : "retry")
    if (!ok && attempt < 8) setTimeout(() => focusWithRetries(el, attempt + 1), 60)
  }

  // Wait until element (or an ancestor) becomes visible
  function waitVisible(el, cb, opts = { timeout: 12000, poll: 120 }) {
    let done = false
    const t0 = Date.now()

    const stop = () => {
      done = true
      try {
        mo.disconnect()
      } catch {}
      clearInterval(iv)
    }

    const tryCall = () => {
      if (done) return
      if (isVisible(el)) {
        stop()
        cb()
      } else if (Date.now() - t0 > opts.timeout) {
        stop()
        console.warn(NS, "Timeout waiting for form visibility.")
      }
    }

    // Observe style/class/DOM changes anywhere (subtree)
    const mo = new MutationObserver(tryCall)
    try {
      mo.observe(document.documentElement, {
        attributes: true,
        childList: true,
        subtree: true,
        attributeFilter: ["style", "class", "hidden", "open"],
      })
    } catch {}
    const iv = setInterval(tryCall, opts.poll)
    tryCall()
  }

  // ---------- core ----------
  function bindEnterAndMaybeFocus(form) {
    if (!form || boundForms.has(form)) return
    boundForms.add(form)
    form.dataset.a11yBound = "1"

    // Enter = submit (capture on the form)
    const onKey = (e) => {
      if (e.key !== "Enter" || e.shiftKey || e.ctrlKey || e.metaKey || e.altKey) return
      const t = e.target
      if (!t) return
      // Exceptions
      if (t.tagName === "TEXTAREA" || t.isContentEditable) return
      if (t.closest('[data-enter="ignore"], [data-no-enter-submit]')) return
      if (t.type === "submit" || t.type === "button") return
      if (t.type === "checkbox" || t.type === "radio" || t.type === "file" || t.type === "range" || t.type === "color")
        return
      if (t.tagName === "SELECT" && t.multiple) return
      if (t.closest("form") !== form) return

      e.preventDefault()
      if (typeof form.requestSubmit === "function") {
        form.requestSubmit()
      } else {
        const btn = form.querySelector('button[type="submit"], input[type="submit"]')
        btn ? btn.click() : form.submit()
      }
    }
    form.addEventListener("keydown", onKey, true)

    // Focus only once per form unless you remove dataset flag
    if (form.dataset.noAutofocus === "1") {
      return
    }

    const doFocus = () => {
      if (form.dataset.a11yFocusedOnce === "1") return
      // Prefer a form that is in/near viewport if many exist
      if (!inViewport(form) && document.querySelector("form[data-a11yFocusedOnce='1']")) {
        // another form already took focus earlier
        return
      }
      const target = pickFocusTarget(form)
      requestAnimationFrame(() => setTimeout(() => focusWithRetries(target), 0))
      form.dataset.a11yFocusedOnce = "1"
    }

    if (isVisible(form)) {
      doFocus()
    } else {
      waitVisible(form, () => {
        doFocus()
      })
    }
  }

  function scanAllForms() {
    const forms = Array.from(document.getElementsByTagName("form"))
    if (!forms.length) {
      return
    }
    const vis = forms.filter(isVisible).length
    forms.forEach(bindEnterAndMaybeFocus)
  }

  // global observer: new forms added dynamically
  const globalObserver = new MutationObserver((muts) => {
    let touched = false
    for (const m of muts) {
      if (m.type === "childList") {
        if (m.addedNodes && m.addedNodes.length) {
          m.addedNodes.forEach((n) => {
            if (n.nodeType === 1 && (n.tagName === "FORM" || n.querySelector?.("form"))) {
              touched = true
            }
          })
        }
      }
    }
    if (touched) {
      scanAllForms()
    }
  })

  try {
    globalObserver.observe(document.documentElement, { childList: true, subtree: true })
  } catch (_) {}

  // Expose for manual trigger (debug)
  window.A11Y = {
    scanNow: scanAllForms,
    _debug: { isVisible, pickFocusTarget },
  }

  // Auto-run (no manual activation needed)
  if (document.readyState === "complete" || document.readyState === "interactive") {
    setTimeout(scanAllForms, 0)
  } else {
    document.addEventListener("DOMContentLoaded", scanAllForms)
  }
  window.addEventListener("load", scanAllForms)

  // Focus inside Bootstrap modals
  document.addEventListener("shown.bs.modal", (e) => {
    scanAllForms()
  })
})()

function get_url_params(q, attribute) {
  var hash
  if (q != undefined) {
    q = q.split("&")
    for (var i = 0; i < q.length; i++) {
      hash = q[i].split("=")
      if (hash[0] == attribute) {
        return hash[1]
      }
    }
  }
}

function setCheckbox(value, table_id) {
  var checkboxes = $("#" + table_id + " input:checkbox")
  $.each(checkboxes, function (index, checkbox) {
    checkbox.checked = value
    if (value) {
      $(checkbox).parentsUntil("tr").parent().addClass("row_selected")
    } else {
      $(checkbox).parentsUntil("tr").parent().removeClass("row_selected")
    }
  })

  return false
}

function action_click(element, table_id) {
  var d = $("#" + table_id)
  var confirmMessage = $(element).attr("data-confirm") || "ConfirmYourChoice"
  if (!confirm(confirmMessage)) {
    return false
  } else {
    var action = $(element).attr("data-action")
    $("#" + table_id + ' input[name="action"]').attr("value", action)
    d.submit()

    return false
  }
}

/**
 * Generic function to replace the deprecated jQuery toggle function
 * @param inId          : id of block to hide / unhide
 * @param inIdTxt       : id of the button
 * @param inTxtHide     : text one of the button
 * @param inTxtUnhide   : text two of the button
 * @todo : allow to detect if text is from a button or from a <a>
 */
function hideUnhide(inId, inIdTxt, inTxtHide, inTxtUnhide) {
  if ($("#" + inId).css("display") == "none") {
    $("#" + inId).show(400)
    $("#" + inIdTxt).attr("value", inTxtUnhide)
  } else {
    $("#" + inId).hide(400)
    $("#" + inIdTxt).attr("value", inTxtHide)
  }
}

function expandColumnToggle(buttonSelector, col1Info, col2Info) {
  $(buttonSelector).on("click", function (e) {
    e.preventDefault()

    col1Info = $.extend(
      {
        selector: "",
        width: 4,
      },
      col1Info,
    )
    col2Info = $.extend(
      {
        selector: "",
        width: 8,
      },
      col2Info,
    )

    if (!col1Info.selector || !col2Info.selector) {
      return
    }

    var col1 = $(col1Info.selector),
      col2 = $(col2Info.selector)

    $("#expand").toggleClass("hide")
    $("#contract").toggleClass("hide")

    if (col2.is(".col-md-" + col2Info.width)) {
      col2.removeClass("col-md-" + col2Info.width).addClass("col-md-12")
      col1.removeClass("col-md-" + col1Info.width).addClass("hide")

      return
    }

    col2.removeClass("col-md-12").addClass("col-md-" + col2Info.width)
    col1.removeClass("hide").addClass("col-md-" + col1Info.width)
  })
}

function addMainEvent(elm, evType, fn, useCapture) {
  if (elm.addEventListener) {
    elm.addEventListener(evType, fn, useCapture)

    return true
  } else if (elm.attachEvent) {
    elm.attachEvent("on" + evType, fn)
  } else {
    elm["on" + evType] = fn
  }
}

window.copyTextToClipBoard = function (elementId) {
  var copyText = document.getElementById(elementId)

  if (copyText) {
    copyText.select()
    document.execCommand("copy")
  }
}

function toggleModal(modalID) {
  document.getElementById(modalID).classList.toggle("hidden")
  document.getElementById(modalID + "-backdrop").classList.toggle("hidden")
  document.getElementById(modalID).classList.toggle("flex")
  document.getElementById(modalID + "-backdrop").classList.toggle("flex")
}

// Expose functions to be use inside chamilo.
// @todo check if there's a better way to expose functions.
window.expandColumnToggle = expandColumnToggle
window.get_url_params = get_url_params
window.setCheckbox = setCheckbox
window.action_click = action_click
window.hideUnhide = hideUnhide
window.addMainEvent = addMainEvent
//window.showTemplates = showTemplates;

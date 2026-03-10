/* For licensing terms, see /license.txt */

function normalizeLocale(locale) {
  return locale.split("_")[0]
}

function findByLang(selector, isoCode, fullLocale) {
  var matches = document.querySelectorAll(selector.replace("{lang}", isoCode))
  if (matches.length === 0 && fullLocale !== isoCode) {
    matches = document.querySelectorAll(selector.replace("{lang}", fullLocale))
  }
  return matches
}

function showMatches(matches) {
  matches.forEach(function (el) {
    el.classList.remove("hidden")
    el.style.display = ""
  })
}

export default function translateHtml() {
  if (!window.user || !window.user.locale) {
    return
  }

  var fullLocale = window.user.locale
  var isoCode = normalizeLocale(fullLocale)

  var translateElements = document.querySelectorAll(".mce-translatehtml")
  if (translateElements.length > 0) {
    translateElements.forEach(function (el) {
      el.style.display = "none"
    })
    showMatches(findByLang('[lang="{lang}"].mce-translatehtml', isoCode, fullLocale))
  }

  // it checks content from old version
  var langSpans = document.querySelectorAll("span[lang]")
  var langs = [...langSpans].filter(function (span) {
    return !span.classList.contains("mce-translatehtml")
  })

  if (langs.length > 0) {
    langs.forEach(function (el) {
      el.style.display = "none"
    })
    showMatches(findByLang('span[lang="{lang}"]:not(.mce-translatehtml)', isoCode, fullLocale))
  }
}

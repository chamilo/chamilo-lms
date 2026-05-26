/* For licensing terms, see /license.txt */

function normalizeLocale(locale) {
  return String(locale || "").replace("-", "_")
}

function buildLocaleCandidates(locale) {
  var normalizedLocale = normalizeLocale(locale)
  var isoCode = normalizedLocale.split("_")[0]
  var candidates = []

  function addCandidate(value) {
    if (value && candidates.indexOf(value) === -1) {
      candidates.push(value)
    }
  }

  addCandidate(isoCode)
  addCandidate(normalizedLocale)
  addCandidate(normalizedLocale.replace("_", "-"))

  return candidates
}

function findByLang(selector, candidates) {
  for (var i = 0; i < candidates.length; i++) {
    var matches = document.querySelectorAll(selector.replace("{lang}", candidates[i]))

    if (matches.length > 0) {
      return matches
    }
  }

  return []
}

function findByLangIn(container, selector, candidates) {
  for (var i = 0; i < candidates.length; i++) {
    var matches = container.querySelectorAll(selector.replace("{lang}", candidates[i]))

    if (matches.length > 0) {
      return matches
    }
  }

  return []
}

function hideMatches(matches) {
  matches.forEach(function (el) {
    el.style.display = "none"
  })
}

function showMatches(matches) {
  matches.forEach(function (el) {
    el.classList.remove("hidden")
    el.style.display = "inline"
  })
}

/**
 * DOM-based: finds and toggles lang-tagged elements already in the page.
 * Used by legacy pages and Learning Paths.
 */
export default function translateHtml() {
  if (!window.user || !window.user.locale) {
    return
  }

  var localeCandidates = buildLocaleCandidates(window.user.locale)
  var translateElements = document.querySelectorAll(".mce-translatehtml")

  if (translateElements.length > 0) {
    hideMatches(translateElements)
    showMatches(findByLang('[lang="{lang}"].mce-translatehtml', localeCandidates))
  }

  // Legacy translate_html content
  var legacyElements = document.querySelectorAll("span[lang]:not(.mce-translatehtml)")

  if (legacyElements.length > 0) {
    hideMatches(legacyElements)
    showMatches(findByLang('span[lang="{lang}"]:not(.mce-translatehtml)', localeCandidates))
  }
}

/**
 * String-based: processes an HTML string and returns it with only the
 * matching language spans visible. Safe to use with Vue's v-html since
 * it does not rely on post-render DOM manipulation.
 */
export function filterTranslatedHtml(html, locale) {
  if (!html || !locale) {
    return html
  }

  var candidates = buildLocaleCandidates(locale)
  var container = document.createElement("div")
  container.innerHTML = html

  // Editor-created content (.mce-translatehtml)
  var mceElements = container.querySelectorAll(".mce-translatehtml")
  if (mceElements.length > 0) {
    hideMatches(mceElements)
    showMatches(findByLangIn(container, '[lang="{lang}"].mce-translatehtml', candidates))
  }

  // Legacy content (span[lang])
  var legacyElements = container.querySelectorAll("span[lang]:not(.mce-translatehtml)")
  if (legacyElements.length > 0) {
    hideMatches(legacyElements)
    showMatches(findByLangIn(container, 'span[lang="{lang}"]:not(.mce-translatehtml)', candidates))
  }

  return container.innerHTML
}

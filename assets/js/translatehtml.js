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

function hideMatches(matches) {
  matches.forEach(function (el) {
    el.style.display = "none"
  })
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

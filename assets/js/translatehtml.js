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

/**
 * Builds the ordered list of language candidates to try: the viewer's own
 * locale first, then each fallback locale in turn (e.g. course language,
 * platform default language), each expanded to [iso, xx_YY, xx-YY]. This is
 * what lets a course language "en" match a block tagged lang="en_US" — never
 * compare locale strings directly, always go through this expansion.
 */
function buildFallbackCandidates(locale, fallbackLocales) {
  var candidates = buildLocaleCandidates(locale)

  ;(fallbackLocales || []).forEach(function (fallbackLocale) {
    buildLocaleCandidates(fallbackLocale).forEach(function (candidate) {
      if (candidates.indexOf(candidate) === -1) {
        candidates.push(candidate)
      }
    })
  })

  return candidates
}

function findByLangIn(root, selector, candidates) {
  for (var i = 0; i < candidates.length; i++) {
    var matches = root.querySelectorAll(selector.replace("{lang}", candidates[i]))

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
    el.style.display = el.tagName.toLowerCase() === "span" ? "inline" : "block"
  })
}

/**
 * Hides every element of a lang-tagged group, then re-shows whichever
 * candidate matches first. If none of the candidates match anything, falls
 * back to the first language actually present in the group rather than
 * leaving the group blank — mirrors the legacy
 * api_get_filtered_multilingual_HTML_string() behavior.
 */
function applyGroup(root, allSelector, langSelector, candidates) {
  var group = root.querySelectorAll(allSelector)

  if (group.length === 0) {
    return
  }

  hideMatches(group)

  var matches = findByLangIn(root, langSelector, candidates)

  if (matches.length === 0) {
    matches = findByLangIn(root, langSelector, buildLocaleCandidates(group[0].getAttribute("lang")))
  }

  showMatches(matches)
}

/**
 * DOM-based: finds and toggles lang-tagged elements already in the page.
 * Used by legacy pages and Learning Paths.
 *
 * @param {string[]} [fallbackLocales] Locales to try, in order, when the
 *   viewer's own locale has no matching block (e.g. course language,
 *   platform default language). Defaults to window.course_language and
 *   window.platform_language, set by vue_js_setup.html.twig.
 */
export default function translateHtml(fallbackLocales) {
  if (undefined === fallbackLocales) {
    fallbackLocales = [window.course_language, window.platform_language].filter(Boolean)
  }

  var userLocale = window.user && window.user.locale ? window.user.locale : ""
  var candidates = buildFallbackCandidates(userLocale, fallbackLocales)

  if (candidates.length === 0) {
    return
  }

  applyGroup(document, ".mce-translatehtml", '[lang="{lang}"].mce-translatehtml', candidates)

  // Legacy translate_html content
  applyGroup(document, "span[lang]:not(.mce-translatehtml)", 'span[lang="{lang}"]:not(.mce-translatehtml)', candidates)
}

/**
 * String-based: processes an HTML string and returns it with only the
 * matching language spans visible. Safe to use with Vue's v-html since
 * it does not rely on post-render DOM manipulation.
 *
 * @param {string} html
 * @param {string} locale The viewer's own locale.
 * @param {string[]} [fallbackLocales] Locales to try, in order, when the
 *   viewer's own locale has no matching block (e.g. course language,
 *   platform default language).
 */
export function filterTranslatedHtml(html, locale, fallbackLocales = []) {
  if (!html) {
    return html
  }

  var candidates = buildFallbackCandidates(locale, fallbackLocales)

  if (candidates.length === 0) {
    return html
  }

  var container = document.createElement("div")
  container.innerHTML = html

  // Editor-created content (.mce-translatehtml)
  applyGroup(container, ".mce-translatehtml", '[lang="{lang}"].mce-translatehtml', candidates)

  // Legacy content (span[lang])
  applyGroup(container, "span[lang]:not(.mce-translatehtml)", 'span[lang="{lang}"]:not(.mce-translatehtml)', candidates)

  return container.innerHTML
}

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

function findByLang(elements, candidates) {
  for (var i = 0; i < candidates.length; i++) {
    var matches = elements.filter(function (el) {
      return el.getAttribute("lang") === candidates[i]
    })

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

/**
 * Removes non-matching language elements from the DOM entirely, instead of
 * merely CSS-hiding them. Plain-text extraction (textContent, innerText, or
 * regex tag-stripping, as done when previewing translated HTML in table
 * cells) ignores `display: none` and would otherwise concatenate every
 * language's text. Visually this is equivalent to hiding, since neither
 * approach renders the element.
 */
function removeMatches(matches) {
  matches.forEach(function (el) {
    el.parentNode && el.parentNode.removeChild(el)
  })
}

function showMatches(matches) {
  matches.forEach(function (el) {
    el.classList.remove("hidden")
    el.style.display = el.tagName.toLowerCase() === "span" ? "inline" : "block"
  })
}

/**
 * Groups elements by their immediate parent. A page (or an editor field) can
 * contain several *independent* lang-tagged groups (e.g. the course "about"
 * page renders one multilingual block per description section) — they must
 * never be pooled together, or a language match in one group hides an
 * unrelated group that has no matching language at all.
 */
function groupByParent(elements) {
  var parents = []
  var groups = []

  elements.forEach(function (el) {
    var index = parents.indexOf(el.parentNode)

    if (index === -1) {
      parents.push(el.parentNode)
      groups.push([el])
    } else {
      groups[index].push(el)
    }
  })

  return groups
}

/**
 * Within each independent lang-tagged group found under root, hides every
 * element then re-shows whichever candidate matches first. If none of the
 * candidates match anything in that group, falls back to the first language
 * actually present in the group rather than leaving it blank — mirrors the
 * legacy api_get_filtered_multilingual_HTML_string() behavior.
 */
function applyGroup(root, allSelector, candidates, removeNonMatching) {
  var all = Array.prototype.slice.call(root.querySelectorAll(allSelector))

  if (all.length === 0) {
    return
  }

  groupByParent(all).forEach(function (group) {
    var matches = findByLang(group, candidates)

    if (matches.length === 0) {
      matches = findByLang(group, buildLocaleCandidates(group[0].getAttribute("lang")))
    }

    if (removeNonMatching) {
      removeMatches(group.filter(function (el) { return matches.indexOf(el) === -1 }))
    } else {
      hideMatches(group)
    }

    showMatches(matches)
  })
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

  applyGroup(document, ".mce-translatehtml", candidates)

  // Legacy translate_html content
  applyGroup(document, "span[lang]:not(.mce-translatehtml)", candidates)
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
  applyGroup(container, ".mce-translatehtml", candidates, true)

  // Legacy content (span[lang])
  applyGroup(container, "span[lang]:not(.mce-translatehtml)", candidates, true)

  return container.innerHTML
}

import { createI18n } from "vue-i18n"

// Discover the available locale codes without bundling their content: "lazy" mode
// gives each matched JSON file its own on-demand chunk instead of inlining all
// ~70 supported languages into the main entry (that used to cost ~16MB of the
// ~21MB vue.js bundle, downloaded by every visitor regardless of their language).
const localeContext = require.context("../locales", false, /[A-Za-z0-9-_,\s]+\.json$/i, "lazy")

const localeLoaders = {}
localeContext.keys().forEach((key) => {
  const m = key.match(/([A-Za-z0-9-_]+)\.json$/i)
  if (m && m[1]) {
    localeLoaders[m[1]] = () => localeContext(key)
  }
})

/**
 * The languages the server declares in window.languages (TwigListener).
 *
 * @returns {Array<Object>} the list, or [] when the global is missing
 */
function declaredLanguages() {
  return Array.isArray(window.languages) ? window.languages : []
}

// A sub-language has no chunk (require.context ran before it existed), but it must still
// count as available or resolveBestLocale() would answer its parent instead.
const availableLocales = [
  ...Object.keys(localeLoaders),
  ...declaredLanguages()
    .map((language) => language.isocode)
    .filter((isocode) => isocode && !localeLoaders[isocode]),
]

// English is spelled en_US in assets/locales and in translations: there is no en.json.
const FALLBACK_LOCALE = "en_US"

// Resolve the best available bundle for a requested code
function resolveBestLocale(requested, keys) {
  if (!keys.length) return { requested, resolved: FALLBACK_LOCALE, base: null }

  const lowerMap = new Map(keys.map((k) => [k.toLowerCase(), k]))
  const raw = String(requested || "").trim()
  const norm = raw.replace(/-/g, "_")
  const base = norm.toLowerCase().split("_")[0] || norm.toLowerCase()

  const existsCI = (k) => lowerMap.has(String(k).toLowerCase())
  const pickCI = (k) => lowerMap.get(String(k).toLowerCase())

  // 1) exact (case-insensitive)
  if (existsCI(norm)) return { requested: raw, resolved: pickCI(norm), base }

  // 2) try opposite normalization
  const dash = norm.replace(/_/g, "-")
  if (existsCI(dash)) return { requested: raw, resolved: pickCI(dash), base }

  // 3) try base (e.g. "es")
  if (existsCI(base)) return { requested: raw, resolved: pickCI(base), base }

  // 4) first file starting with base_ or base-
  const prefUnd = keys.find((k) => k.toLowerCase().startsWith(base + "_"))
  if (prefUnd) return { requested: raw, resolved: prefUnd, base }
  const prefDash = keys.find((k) => k.toLowerCase().startsWith(base + "-"))
  if (prefDash) return { requested: raw, resolved: prefDash, base }

  // 5) fallback to English
  return { requested: raw, resolved: FALLBACK_LOCALE, base }
}

// Chamilo sub-languages (an admin-created variant of an existing language, e.g.
// "fr_69" derived from "fr_FR") only override a handful of strings and rely on
// falling back to their real parent for everything else. That parent isn't
// derivable from the code itself -- this codebase has no bare "xx.json" bundle
// for any language, so naively splitting on "_" (e.g. "fr_69" -> "fr") never
// matches an actual file and silently degrades straight to English. The real
// parent/child relationships are published in window.languages (see
// TwigListener::__invoke() / LanguageRepository::getParentIsocodesByChildIsocode()).
function findRealParentLocale(code, keys) {
  const languages = window.languages || []
  const entry = languages.find((l) => l.isocode === code)
  const parentIso = entry?.parentIsocode

  return parentIso && keys.includes(parentIso) ? parentIso : null
}

// Build fallback chain (prefer the real parent language, then the bare base
// code if a bundle for it happens to exist, then English)
function buildFallbackChain(base, resolved, keys) {
  const chain = []
  const realParent = findRealParentLocale(resolved, keys)

  if (realParent && realParent !== resolved) {
    chain.push(realParent)
  } else if (base && base !== resolved && keys.includes(base)) {
    chain.push(base)
  }

  if (!chain.includes(FALLBACK_LOCALE) && resolved !== FALLBACK_LOCALE) {
    chain.push(FALLBACK_LOCALE)
  }

  return chain
}

const loadedLocales = new Set()

/**
 * Tells whether the server declares this code as a sub-language (it has a parent).
 *
 * @param {string} code - locale code, e.g. "cs_66"
 * @returns {boolean} true when the server owns this locale's terms
 */
function isSubLanguage(code) {
  return declaredLanguages().some((language) => language.isocode === code && language.parentIsocode)
}

/**
 * Fetches a sub-language's own terms from LocaleController.
 *
 * @param {string} code - locale code, e.g. "cs_66"
 * @returns {Promise<Object|null>} the messages, or null when they cannot be read
 */
async function fetchServerLocale(code) {
  try {
    const response = await fetch(`/locales/${encodeURIComponent(code)}.json`, {
      headers: { Accept: "application/json" },
    })

    return response.ok ? await response.json() : null
  } catch {
    return null
  }
}

// Downloads one locale's messages on first use and registers them with vue-i18n.
// A no-op for locales already loaded (repeated switches back and forth are free).
async function ensureLocaleLoaded(code) {
  if (!code || loadedLocales.has(code)) {
    return
  }

  // The server wins for a sub-language: a file of the same name under assets/locales is a
  // build-time snapshot, and it goes stale as soon as an administrator edits a term.
  if (isSubLanguage(code)) {
    const messages = await fetchServerLocale(code)

    if (messages) {
      i18n.global.setLocaleMessage(code, messages)
      loadedLocales.add(code)

      return
    }
    // Fall through to the frozen bundle, or to the parent through the fallback chain.
  }

  if (!localeLoaders[code]) {
    return
  }

  const mod = await localeLoaders[code]()
  i18n.global.setLocaleMessage(code, mod?.default ?? mod)
  loadedLocales.add(code)
}

async function loadLocaleWithFallbacks(resolved, base) {
  const chain = buildFallbackChain(base, resolved, availableLocales)
  await Promise.all([resolved, ...chain].map(ensureLocaleLoaded))

  return chain
}

// The server (LocaleSubscriber) resolves the locale for every full page load and
// prints it in <html data-lang>: it already factors platform/user/course settings,
// the language priorities, the ?_locale override and the browser Accept-Language.
// That answer is authoritative at boot. Client-side route changes are handled at
// runtime by setLocale() (driven by the useLocale composable) — never persist the
// locale on the client, a stored value can only disagree with the server.
const initialHtmlLocale = document.documentElement.dataset?.lang || "en_US"
const initial = resolveBestLocale(initialHtmlLocale, availableLocales)

// NOTE: do NOT create runtime aliases; use the resolved bundle directly
const i18n = createI18n({
  legacy: false,
  globalInjection: true, // allow using $t in Options API
  locale: initial.resolved, // use an existing bundle to avoid remounts
  fallbackLocale: buildFallbackChain(initial.base, initial.resolved, availableLocales),
  messages: {},
})

// Resolves once the boot locale's messages (and its fallback chain -- the real
// parent locale for a sub-language, or else "<base>" -- plus en_US) are loaded
// and registered. main.js awaits this before mounting the app, so templates
// never render raw translation keys.
export const i18nReady = loadLocaleWithFallbacks(initial.resolved, initial.base)

/**
 * Switches the interface locale at runtime (no page reload), e.g. when a
 * client-side navigation enters or leaves a course with its own language.
 * Waits for the target locale's messages (and fallback chain) to be loaded
 * before switching, so the UI never flashes untranslated keys mid-switch.
 *
 * @param {string} code - requested locale code (e.g. "es", "en_US", "pt-BR")
 */
export async function setLocale(code) {
  const target = resolveBestLocale(code, availableLocales)
  const chain = await loadLocaleWithFallbacks(target.resolved, target.base)

  // Update fallback chain and current locale reactively
  i18n.global.fallbackLocale.value = chain
  i18n.global.locale.value = target.resolved // switch to an existing bundle

  if (typeof document !== "undefined") {
    document.documentElement.dataset.lang = target.resolved
  }
}

export default i18n

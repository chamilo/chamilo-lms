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

const availableLocales = Object.keys(localeLoaders)

// Resolve the best available bundle for a requested code
function resolveBestLocale(requested, keys) {
  if (!keys.length) return { requested, resolved: "en", base: null }

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
  return { requested: raw, resolved: "en", base }
}

// Build fallback chain (prefer base, then English)
function buildFallbackChain(base, resolved, keys) {
  const chain = []
  if (base && base !== resolved && keys.includes(base)) chain.push(base)
  chain.push("en")
  return chain
}

const loadedLocales = new Set()

// Downloads one locale's messages on first use and registers them with vue-i18n.
// A no-op for locales already loaded (repeated switches back and forth are free).
async function ensureLocaleLoaded(code) {
  if (!code || loadedLocales.has(code) || !localeLoaders[code]) {
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

// Resolves once the boot locale's messages (and its fallback chain, at most
// "<base>" and "en") are loaded and registered. main.js awaits this before
// mounting the app, so templates never render raw translation keys.
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

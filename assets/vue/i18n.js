import { createI18n } from "vue-i18n"

// Load all locale JSON files inside assets/locales (flat folder)
function loadLocaleMessages() {
  const ctx = require.context("../locales", false, /[A-Za-z0-9-_,\s]+\.json$/i)
  const messages = {}
  ctx.keys().forEach((key) => {
    const m = key.match(/([A-Za-z0-9-_]+)\.json$/i)
    if (m && m[1]) {
      const localeKey = m[1]
      messages[localeKey] = ctx(key)
    }
  })

  return messages
}

// Resolve the best available bundle for a requested code
function resolveBestLocale(requested, messages) {
  const keys = Object.keys(messages)
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
function buildFallbackChain(base, resolved, messages) {
  const chain = []
  if (base && base !== resolved && messages[base]) chain.push(base)
  chain.push("en")
  return chain
}

const messages = loadLocaleMessages()

// Prefer previously chosen locale, otherwise <html lang>, otherwise "en"
const stored = typeof localStorage !== "undefined" ? localStorage.getItem("app_locale") : null
const initialHtmlLocale = stored || document.documentElement.dataset?.lang || "en_US"
const initial = resolveBestLocale(initialHtmlLocale, messages)

// NOTE: do NOT create runtime aliases; use the resolved bundle directly
const i18n = createI18n({
  legacy: false,
  globalInjection: true, // allow using $t in Options API
  locale: initial.resolved, // use an existing bundle to avoid remounts
  fallbackLocale: buildFallbackChain(initial.base, initial.resolved, messages),
  messages,
})

// Public API: switch locale at runtime (no page reload)
export function setLocale(code) {
  const target = resolveBestLocale(code, messages)

  // Update fallback chain and current locale reactively
  i18n.global.fallbackLocale.value = buildFallbackChain(target.base, target.resolved, messages)
  i18n.global.locale.value = target.resolved // switch to an existing bundle

  if (typeof document !== "undefined") {
    document.documentElement.dataset.lang = target.resolved
  }
  if (typeof localStorage !== "undefined") {
    localStorage.setItem("app_locale", target.resolved)
  }
}

export default i18n

import { usePlatformConfig } from "../store/platformConfig"
import { useSecurityStore } from "../store/securityStore"
import { useCidReqStore } from "../store/cidReq"
import { useCourseSettings } from "../store/courseSettingStore"
import { computed, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"

export function useLocale() {
  const router = useRouter()
  const route = useRoute()

  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()
  const cidReqStore = useCidReqStore()
  const courseSettingsStore = useCourseSettings()

  const appLocale = ref(document.documentElement.dataset.lang)

  const localeList = computed(() => {
    const list = {}

    list["platform_lang"] = platformConfigStore.getSetting("language.platform_language")
    list["user_profil_lang"] = securityStore.user ? securityStore.user.locale : null

    let courseLang = null

    if (
      courseSettingsStore.getSetting("show_course_in_user_language") === "1" &&
      securityStore.user &&
      securityStore.user.locale
    ) {
      courseLang = securityStore.user.locale
    } else if (cidReqStore.course) {
      courseLang = cidReqStore.course.courseLanguage
    }

    list["course_lang"] = courseLang

    return list
  })

  watch(
    localeList,
    (newLocaleList) => {
      const priorityList = ["language_priority_1", "language_priority_2", "language_priority_3", "language_priority_4"]

      for (const priority of priorityList) {
        const setting = platformConfigStore.getSetting(`language.${priority}`)

        if (setting && newLocaleList[setting]) {
          appLocale.value = newLocaleList[setting]

          break
        }
      }
    },
    { immediate: true },
  )

  const defaultLanguage = { originalName: "English", isocode: "en_US" }

  /**
   * @type {{originalName: string, isocode: string}[]}
   */
  const languageList = window.languages || [defaultLanguage]

  /**
   * @type {{originalName: string, isocode: string}}
   */
  const currentLanguageFromList =
    languageList.find((language) => document.documentElement.dataset.lang === language.isocode) || defaultLanguage

  /**
   * @param {string} isoCode
   */
  function reloadWithLocale(isoCode) {
    const newUrl = router.resolve({
      path: route.path,
      query: {
        _locale: isoCode,
      },
    })

    window.location.href = newUrl.fullPath
  }

  function getOriginalLanguageName(isoCode) {
    const lang = languageList.find((l) => l.isocode === isoCode)
    return lang?.originalName ?? isoCode.toUpperCase()
  }

  const appParentLocale = computed(() => useParentLocale(appLocale.value))

  // Simple in-memory cache for API lookups
  const __apiLangCache = new Map()

  function normalizeIso(iso) {
    // Normalize to DB and BCP-47 variants and extract parent (e.g., "pt_BR" -> "pt")
    if (!iso) return { db: "", bcp47: "", parent: "" }
    const u = String(iso).trim()
    return {
      db: u.replace("-", "_"), // pt-BR -> pt_BR (DB)
      bcp47: u.replace("_", "-"), // pt_BR -> pt-BR (BCP-47)
      parent: u.split(/[-_]/)[0], // pt_BR -> pt
    }
  }

  /**
   * Get a human-readable language name using the current UI locale (synchronous).
   * Does not depend on window.languages; falls back to it if Intl fails.
   */
  function getLanguageName(iso, displayLocale = null) {
    if (!iso) return "-"
    const tag = String(iso).replace("_", "-")
    const ui = displayLocale || appLocale.value || document.documentElement.dataset.lang || "en-US"
    try {
      const dn = new Intl.DisplayNames([ui], { type: "language" })
      return dn.of(tag) || iso.toUpperCase()
    } catch {
      const hit = (window.languages || []).find((l) => l.isocode === iso)
      return hit?.originalName || hit?.original_name || hit?.english_name || iso.toUpperCase()
    }
  }

  /**
   * Fetch language name from API by isocode (includes unavailable languages).
   * Tries exact, variant, then parent. Results are cached.
   */
  async function fetchLanguageNameFromApi(iso) {
    if (!iso) return "-"
    if (__apiLangCache.has(iso)) return __apiLangCache.get(iso)

    const { db, bcp47, parent } = normalizeIso(iso)

    async function hit(q) {
      const r = await fetch(`/api/languages?isocode=${encodeURIComponent(q)}`)
      if (!r.ok) return null
      const j = await r.json()
      const arr = j["hydra:member"] || j
      return Array.isArray(arr) && arr.length ? arr[0] : null
    }

    let row = await hit(db)
    if (!row && bcp47 !== db) row = await hit(bcp47)
    if (!row && parent && parent !== db) row = await hit(parent)

    const name = row?.originalName || row?.original_name || row?.englishName || row?.english_name || iso.toUpperCase()

    __apiLangCache.set(iso, name)
    return name
  }

  return {
    appLocale,
    appParentLocale,
    languageList,
    currentLanguageFromList,
    reloadWithLocale,
    getOriginalLanguageName,
    getLanguageName,
    fetchLanguageNameFromApi,
  }
}

/**
 * @param {string} localeName
 */
export function useParentLocale(localeName) {
  const parts = localeName.split("_")

  if (parts.length > 0) {
    return parts[0]
  }

  return localeName
}

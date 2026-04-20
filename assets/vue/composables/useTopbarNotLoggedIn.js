import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import axios from "axios"
import { useLocale } from "./locale"
import { usePlatformConfig } from "../store/platformConfig"

export function useTopbarNotLoggedIn() {
  const { t } = useI18n()
  const router = useRouter()
  const route = useRoute()
  const platformConfigStore = usePlatformConfig()
  const { languageList, currentLanguageFromList, reloadWithLocale } = useLocale()

  const isUndefinedUrl = computed(() => {
    const r = route.name?.toString().toLowerCase() || ""

    return r.includes("undefined-url") || route.path.includes("/error/undefined-url")
  })

  function normalizeLocaleCode(value) {
    return String(value || "")
      .trim()
      .replace("-", "_")
      .toLowerCase()
  }

  function normalizeBooleanFlag(value) {
    if (typeof value === "boolean") {
      return value
    }

    if (typeof value === "number") {
      return value === 1
    }

    if (typeof value === "string") {
      const normalized = value.trim().toLowerCase()
      return ["1", "true", "yes", "on"].includes(normalized)
    }

    return false
  }

  const currentLanguageLabel = computed(() => {
    const rawLocale = Array.isArray(route.query?._locale) ? route.query._locale[0] : route.query?._locale
    const urlLocale = normalizeLocaleCode(rawLocale)

    if (urlLocale) {
      const shortLocale = urlLocale.split("_")[0]

      const match =
        languageList.find((lang) => normalizeLocaleCode(lang?.isocode) === urlLocale) ||
        languageList.find((lang) => {
          const code = normalizeLocaleCode(lang?.isocode)
          const shortCode = code.split("_")[0]

          return shortCode === urlLocale || code === shortLocale || shortCode === shortLocale
        })

      if (match?.originalName) {
        return match.originalName
      }
    }

    return currentLanguageFromList?.originalName || t("Language")
  })

  const currentLocale = computed(() => currentLanguageFromList?.isocode || "en")

  const languageItems = computed(() =>
    languageList.map((lang) => ({
      label: lang.originalName,
      isoCode: lang.isocode,
      command: (e) => reloadWithLocale(e.item.isoCode),
    })),
  )

  const allowRegistration = computed(
    () => platformConfigStore.getSetting("registration.allow_registration") !== "false",
  )

  const buyCoursesConfig = computed(() => platformConfigStore.plugins?.buycourses || {})

  const showBuyCoursesLink = computed(() => normalizeBooleanFlag(buyCoursesConfig.value?.visibleForAnonymousUsers))

  const buyCoursesIndexPath = computed(() => buyCoursesConfig.value?.indexPath || "/plugin/BuyCourses/index.php")

  const flags = ref({ home: false, faq: false, demo: false, contact: false })

  async function resolveVisibility() {
    if (isUndefinedUrl.value) {
      return
    }

    const { data } = await axios.get("/pages/_topbar-visibility", {
      params: { locale: currentLocale.value },
      headers: { "Cache-Control": "no-cache" },
    })

    flags.value = data
  }

  onMounted(resolveVisibility)

  const menuItems = computed(() => {
    if (isUndefinedUrl.value) {
      const items = []

      if (languageList.length > 1) {
        items.push({
          key: "language_selector",
          label: currentLanguageLabel.value,
          items: languageItems.value,
        })
      }

      return items
    }

    const items = []

    if (flags.value.home) {
      items.push({
        label: t("Home"),
        url: router.resolve({ name: "Index" }).href,
      })
    }

    const showCatalogueLink =
      platformConfigStore.getSetting("catalog.course_catalog_published") !== "false" &&
      platformConfigStore.getSetting("catalog.hide_public_link") !== "true" &&
      platformConfigStore.getSetting("catalog.allow_students_to_browse_courses") !== "false"

    if (showCatalogueLink) {
      items.push({
        label: t("Browse courses"),
        url: router.resolve({ name: "CatalogueCourses" }).href,
      })
    }

    if (showBuyCoursesLink.value) {
      items.push({
        label: t("Buy courses"),
        url: buyCoursesIndexPath.value,
      })
    }

    if (flags.value.faq) {
      items.push({
        label: t("FAQ"),
        url: router.resolve({ name: "Faq" }).href,
      })
    }

    if (allowRegistration.value) {
      items.push({
        label: t("Registration"),
        url: "/main/auth/registration.php",
      })
    }

    if (flags.value.demo) {
      items.push({
        label: t("Demo"),
        url: router.resolve({ name: "Demo" }).href,
      })
    }

    if (flags.value.contact) {
      items.push({
        label: t("Contact"),
        url: "/contact",
      })
    }

    if (languageList.length > 1) {
      items.push({
        key: "language_selector",
        label: currentLanguageLabel.value,
        items: languageItems.value,
      })
    }

    return items
  })

  return {
    menuItems,
  }
}

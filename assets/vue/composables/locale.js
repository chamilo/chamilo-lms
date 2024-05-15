import { usePlatformConfig } from "../store/platformConfig"
import { useSecurityStore } from "../store/securityStore"
import { useCidReqStore } from "../store/cidReq"
import { useCourseSettings } from "../store/courseSettingStore"
import { computed, ref, watch } from "vue"

export function useLocale() {
  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()
  const cidReqStore = useCidReqStore()
  const courseSettingsStore = useCourseSettings()

  const appLocale = ref(document.querySelector("html").lang)

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

  return {
    appLocale,
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

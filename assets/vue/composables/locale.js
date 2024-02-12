import {usePlatformConfig} from "../store/platformConfig";
import {useSecurityStore} from "../store/securityStore";
import {useCidReqStore} from "../store/cidReq";
import {computed, ref} from "vue";

export function useLocale() {
  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()
  const cidReqStore = useCidReqStore()

  const localeList = computed(() => {
    const list = {};

    const platformLocale = platformConfigStore.getSetting('language.platform_language')

    if (platformLocale) {
      list['platform_lang'] = platformLocale
    }

    const userLocale = securityStore.user ? securityStore.user.locale : null

    if (userLocale) {
      list['user_profil_lang'] = userLocale
    }

    const courseLocale = cidReqStore.course ? cidReqStore.course.courseLanguage : null

    if (courseLocale) {
      list['course_lang'] = courseLocale
    }

    // @todo check language from request
    //list['user_selected_lang'] = ?

    return list
  });

  const priorityList = [
    'language_priority_1',
    'language_priority_2',
    'language_priority_3',
    'language_priority_4',
  ]

  const appLocale = ref('')

  for (const setting of priorityList) {
    const priority = platformConfigStore.getSetting(`language.${setting}`)

    if (priority && localeList.value[priority]) {
      appLocale.value = localeList.value[priority]

      break
    }
  }

  if (!appLocale.value) {
    appLocale.value = document.querySelector('html').lang
  }

  return {
    appLocale
  }
}

/**
 * @param {string} localeName
 */
export function useParentLocale(localeName) {
  const parts = localeName.split('_')

  if (parts.length > 0) {
    return parts[0]
  }

  return localeName
}

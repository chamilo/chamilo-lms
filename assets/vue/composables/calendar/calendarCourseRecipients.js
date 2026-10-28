import { ref } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import baseService from "../../services/baseService"
import { useCidReqStore } from "../../store/cidReq"

/**
 * The users and groups of the current course a course event can be sent to, as options whose
 * value is "USER:<id>" or "GROUP:<id>".
 *
 * @returns {{options: import("vue").Ref<Array<{value: string, label: string}>>, isLoading: import("vue").Ref<boolean>, loadOptions: Function}}
 */
export function useCalendarCourseRecipients() {
  const { t } = useI18n()
  const { course, session } = storeToRefs(useCidReqStore())

  const options = ref([])
  const isLoading = ref(false)

  /**
   * Loads the options from the API.
   */
  async function loadOptions() {
    isLoading.value = true

    try {
      const params = { cid: course.value?.id }
      if (session.value?.id) {
        params.sid = session.value.id
      }

      const { users = [], groups = [] } = await baseService.get("/api/calendar/course-recipients", params)

      options.value = [
        ...groups.map((group) => ({ value: `GROUP:${group.id}`, label: `${t("Group")}: ${group.label}` })),
        ...users.map((user) => ({ value: `USER:${user.id}`, label: user.label })),
      ]
    } catch (error) {
      console.error("[Calendar] Failed to load the course recipients.", error)
    } finally {
      isLoading.value = false
    }
  }

  return {
    options,
    isLoading,
    loadOptions,
  }
}

import { ref, watchEffect } from "vue"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"
import { checkIsAllowedToEdit } from "../userPermissions"

/**
 * Extracted from Agenda::displayActions
 */
export function useCalendarActionButtons() {
  const cidReqStore = useCidReqStore()
  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()

  const { course } = storeToRefs(cidReqStore)

  const isAllowedToEdit = ref(false)

  checkIsAllowedToEdit(false, true).then((response) => (isAllowedToEdit.value = response))

  const isAllowedToSessionEdit = false

  const courseAllowUserEditAgenda = "0"

  const showAddButton = ref(false)
  const showImportICalButton = ref(false)
  const showImportCourseEventsButton = ref(false)
  const showSessionPlanningButton = ref(false)
  const showMyStudentsScheduleButton = ref(false)

  const isPersonal = !course.value

  watchEffect(() => {
    if (
      isAllowedToEdit.value ||
      (isPersonal &&
        securityStore.isAuthenticated &&
        "true" === platformConfigStore.getSetting("agenda.allow_personal_agenda")) ||
      ("1" === courseAllowUserEditAgenda && securityStore.isAuthenticated && isAllowedToSessionEdit)
    ) {
      showAddButton.value = true
      showImportICalButton.value = true

      if (course.value) {
        if (isAllowedToEdit.value) {
          showImportCourseEventsButton.value = true
        }
      }
    }

    if (!course.value && securityStore.isAuthenticated) {
      showSessionPlanningButton.value = true

      if (securityStore.isStudentBoss || securityStore.isAdmin) {
        showMyStudentsScheduleButton.value = true
      }
    }
  })

  return {
    showAddButton,
    showImportICalButton,
    showImportCourseEventsButton,
    showSessionPlanningButton,
    showMyStudentsScheduleButton,
  }
}

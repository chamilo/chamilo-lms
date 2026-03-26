import { ref, watchEffect } from "vue"
import { storeToRefs } from "pinia"
import { useSecurityStore } from "../../store/securityStore"
import { useCidReqStore } from "../../store/cidReq"
import { useIsAllowedToEdit } from "../userPermissions"

export function useCalendarActionButtons() {
  const securityStore = useSecurityStore()
  const cidReqStore = useCidReqStore()
  const { course } = storeToRefs(cidReqStore)

  const { isAllowedToEdit } = useIsAllowedToEdit({ coach: true })

  const showAddButton = ref(false)
  const showAgendaListButton = ref(false)
  const showSessionPlanningButton = ref(false)
  const showMyStudentsScheduleButton = ref(false)

  watchEffect(() => {
    const isAuthenticated = Boolean(securityStore.isAuthenticated)
    const isPersonal = !course.value

    showAddButton.value = false
    showAgendaListButton.value = false
    showSessionPlanningButton.value = false
    showMyStudentsScheduleButton.value = false

    if (!isAuthenticated) {
      return
    }

    // Calendar/List switch is useful for all authenticated users.
    showAgendaListButton.value = true

    // Basic "Add event": allow personal events for authenticated users.
    if (isPersonal) {
      showAddButton.value = true
      showSessionPlanningButton.value = true
      showMyStudentsScheduleButton.value = true
    } else {
      // Inside a course context: allow add only when user can edit.
      showAddButton.value = Boolean(isAllowedToEdit.value)
    }
  })

  return {
    showAddButton,
    showAgendaListButton,
    showSessionPlanningButton,
    showMyStudentsScheduleButton,
  }
}

import { useCidReqStore } from "../../store/cidReq"
import { useSecurityStore } from "../../store/securityStore"

export function useCalendarInvitations() {
  const cidReqStore = useCidReqStore()
  const securirtyStore = useSecurityStore()

  const isPersonalCalendar = null === cidReqStore.course

  const allowCollectiveInvitations = isPersonalCalendar
  const allowSubscriptions = securirtyStore.isAdmin && isPersonalCalendar

  return {
    allowCollectiveInvitations,
    allowSubscriptions,
  }
}

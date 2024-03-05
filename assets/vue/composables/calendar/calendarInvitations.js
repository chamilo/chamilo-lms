import { useCidReqStore } from "../../store/cidReq"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

export function useCalendarInvitations() {
  const cidReqStore = useCidReqStore()
  const platformConfigStore = usePlatformConfig()
  const securirtyStore = useSecurityStore()

  const isPersonalCalendar = null === cidReqStore.course

  const agendaCollectiveInvitations = "true" === platformConfigStore.getSetting("agenda.agenda_collective_invitations")
  const agendaEventSubscriptions = "true" === platformConfigStore.getSetting("agenda.agenda_event_subscriptions")

  const allowCollectiveInvitations = agendaCollectiveInvitations && isPersonalCalendar
  const allowSubscriptions = securirtyStore.isAdmin && agendaEventSubscriptions && isPersonalCalendar

  return {
    allowCollectiveInvitations,
    allowSubscriptions,
  }
}

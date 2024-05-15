import { ref } from "vue"
import { useSecurityStore } from "../../../store/securityStore"
import sessionService from "../../../services/sessionService"

export function useSession(type) {
  const securityStore = useSecurityStore()

  const sessions = ref([])
  const isLoading = ref(false)

  if (securityStore.isAuthenticated) {
    isLoading.value = true

    sessionService
      .findUserSubscriptions(securityStore.user["@id"], type)
      .then(({ items }) => (sessions.value = items))
      .finally(() => (isLoading.value = false))
  }

  return {
    sessions,
    isLoading,
  }
}

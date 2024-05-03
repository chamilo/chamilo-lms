import { ref, watch } from "vue"
import { useQuery } from "@vue/apollo-composable"
import {
  GET_SESSION_REL_USER_UPCOMMING,
  GET_SESSION_REL_USER_PAST,
  GET_SESSION_REL_USER_CURRENT,
} from "../../../graphql/queries/SessionRelUser"
import { useSecurityStore } from "../../../store/securityStore"

export function useSession(type = null) {
  const securityStore = useSecurityStore()

  const sessions = ref(null)
  const isLoading = ref(false)

  if (securityStore.isAuthenticated) {
    let variables = {
      user: securityStore.user["@id"],
    }

    let finalQuery = GET_SESSION_REL_USER_CURRENT

    if ("upcomming" === type) {
      finalQuery = GET_SESSION_REL_USER_UPCOMMING
    } else if ("past" === type) {
      finalQuery = GET_SESSION_REL_USER_PAST
    }

    isLoading.value = true

    const { result, loading } = useQuery(finalQuery, variables, { fetchPolicy: "no-cache" })

    watch(result, (newResult) => (sessions.value = newResult))
    watch(loading, (newLoading) => (isLoading.value = newLoading))
  }

  return {
    sessions,
    isLoading,
  }
}

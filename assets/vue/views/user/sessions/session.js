import {ref} from "vue"
import {useQuery} from "@vue/apollo-composable"
import {GET_SESSION_REL_USER, GET_SESSION_REL_USER_CURRENT} from "../../../graphql/queries/SessionRelUser"
import {DateTime} from 'luxon'

export function useSession(user, start, end, query) {
  let sessions = ref(null)
  let isLoading = ref(false)

  if (user.value) {
    let userId = user.value.id
    let variables = {
      user: "/api/users/" + userId,
    }

    variables = includeStartDateIfExist(variables, start)
    variables = includeEndDateIfExist(variables, end)
    let finalQuery = getGraphqlQuery(variables)
    if (query !== undefined) {
      finalQuery = query
    }

    isLoading.value = true
    const {result, loading} = useQuery(finalQuery, variables)
    sessions.value = result
    return {
      sessions,
      isLoading: loading,
    }
  }

  return {
    sessions,
    isLoading,
  }
}

const includeStartDateIfExist = (variables, start) => {
  if (start !== undefined && start !== null) {
    if (!DateTime.isDateTime(start)) {
      console.error("You should pass a DateTime instance to useSession start parameter")
    }
    variables.afterStartDate = start.toISO()
  }

  return variables
}

const includeEndDateIfExist = (variables, end) => {
  if (end !== undefined && end !== null) {
    if (!DateTime.isDateTime(end)) {
      console.error("You should pass a DateTime instance to useSession end parameter")
    }
    variables.beforeEndDate = end.toISO()
  }

  return variables
}

const getGraphqlQuery = (variables) => {
  if (Object.hasOwn(variables, "afterStartDate") || Object.hasOwn(variables, "beforeEndDate")) {
    return GET_SESSION_REL_USER
  } else {
    return GET_SESSION_REL_USER_CURRENT
  }
}

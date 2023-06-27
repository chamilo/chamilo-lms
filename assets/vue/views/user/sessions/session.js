import {ref} from "vue"
import {useQuery} from "@vue/apollo-composable"
import {GET_SESSION_REL_USER, GET_SESSION_REL_USER_CURRENT} from "../../../graphql/queries/SessionRelUser"
import {DateTime} from 'luxon'

export function useSession(user, start, end) {
  let sessions = ref(null)
  let isLoading = ref(false)

  if (user.value) {
    isLoading.value = true
    let userId = user.value.id
    let variables = {
      user: "/api/users/" + userId,
    }

    variables = includeStartDateIfExist(variables, start)
    variables = includeEndDateIfExist(variables, end)
    let query = getGraphqlQuery(variables)

    const {result} = useQuery(query, variables)
    sessions.value = result
    isLoading.value = false
  }

  return {
    sessions,
    isLoading,
  }
}

const includeStartDateIfExist = (variables, start) => {
  if (start !== undefined) {
    if (!DateTime.isDateTime(start)) {
      console.error("You should pass a DateTime instance to useSession start parameter")
    }
    variables.afterStartDate = start.toISO()
  }

  return variables
}

const includeEndDateIfExist = (variables, end) => {
  if (end !== undefined) {
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

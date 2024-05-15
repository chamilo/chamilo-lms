import { ref } from "vue"
import { useSecurityStore } from "../../../store/securityStore"
import sessionService from "../../../services/sessionService"
import isEmpty from "lodash/isEmpty"

export function useSession(type) {
  const securityStore = useSecurityStore()

  const isLoading = ref(false)

  const uncategorizedSessions = ref([])
  const categories = ref([])
  const categoriesWithSessions = ref([])

  function getUncategorizedSessions(sessions) {
    return sessions.filter((session) => isEmpty(session.category))
  }

  function getCategories(sessions) {
    let categoryList = []

    sessions.forEach((session) => {
      if (session.category) {
        const alreadyAdded = categoryList.findIndex((cat) => cat["@id"] === session.category["@id"]) >= 0

        if (!alreadyAdded) {
          categoryList.push(session.category)
        }
      }
    })

    return categoryList
  }

  function getCategoriesWithSessions(sessions) {
    let categoriesIn = []

    sessions.forEach(function (session) {
      if (!isEmpty(session.category)) {
        if (categoriesIn[session.category["@id"]] === undefined) {
          categoriesIn[session.category["@id"]] = []
          categoriesIn[session.category["@id"]]["sessions"] = []
        }
        categoriesIn[session.category["@id"]]["sessions"].push(session)
      }
    })

    return categoriesIn
  }

  if (securityStore.isAuthenticated) {
    isLoading.value = true

    sessionService
      .findUserSubscriptions(securityStore.user["@id"], type)
      .then(({ items }) => {
        uncategorizedSessions.value = getUncategorizedSessions(items)
        categories.value = getCategories(items)
        categoriesWithSessions.value = getCategoriesWithSessions(items)
      })
      .finally(() => (isLoading.value = false))
  }

  return {
    isLoading,
    uncategorizedSessions,
    categories,
    categoriesWithSessions,
  }
}

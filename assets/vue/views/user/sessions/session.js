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

  /**
   * @param {Array<object>} sessions
   * @returns {Map<string, { sessions }>}
   */
  function getCategoriesWithSessions(sessions) {
    let categoriesIn = new Map()

    sessions.forEach(function (session) {
      if (isEmpty(session.category)) {
        return
      }

      let sessionsInCategory = []

      if (categoriesIn.has(session.category["@id"])) {
        sessionsInCategory = categoriesIn.get(session.category["@id"]).sessions
      }

      sessionsInCategory.push(session)

      categoriesIn.set(session.category["@id"], { sessions: sessionsInCategory })
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

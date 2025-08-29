import { ref } from "vue"
import { useSecurityStore } from "../../../store/securityStore"
import sessionService from "../../../services/sessionService"
import isEmpty from "lodash/isEmpty"

export function useSession(type) {
  const securityStore = useSecurityStore()

  const isLoading = ref(false)

  const uncategorizedSessions = ref([])
  const categories = ref([])
  const categoriesWithSessions = ref(new Map())

  let paginationParams = {
    page: 1,
  }

  function loadUncategorizedSessions(sessions) {
    uncategorizedSessions.value.push(...sessions.filter((session) => isEmpty(session.category)))
  }

  /**
   * @param {Object[]} sessions
   */
  function loadCategories(sessions) {
    sessions.forEach((session) => {
      if (session.category) {
        const alreadyAdded = categories.value.findIndex((cat) => cat["@id"] === session.category["@id"]) >= 0

        if (!alreadyAdded) {
          categories.value.push(session.category)
        }
      }
    })
  }

  /**
   * @param {Array<object>} sessions
   */
  function loadCategoriesWithSessions(sessions) {
    sessions.forEach(function (session) {
      if (isEmpty(session.category)) {
        return
      }

      let sessionsInCategory = []

      if (categoriesWithSessions.value.has(session.category["@id"])) {
        sessionsInCategory = categoriesWithSessions.value.get(session.category["@id"]).sessions
      }

      sessionsInCategory.push(session)

      categoriesWithSessions.value.set(session.category["@id"], { sessions: sessionsInCategory })
    })
  }

  async function getSessions() {
    if (securityStore.isAuthenticated) {
      isLoading.value = true

      try {
        const { items, nextPageParams } = await sessionService.findUserSubscriptions(securityStore.user["@id"], type, {
          ...paginationParams,
        })

        paginationParams = nextPageParams ? { ...nextPageParams } : null

        loadUncategorizedSessions(items)
        loadCategories(items)
        loadCategoriesWithSessions(items)
      } finally {
        isLoading.value = false
      }
    }
  }

  async function reload() {
    if (null !== paginationParams) {
      await getSessions()
    }
  }

  getSessions().then(() => {})

  return {
    isLoading,
    reload,
    uncategorizedSessions,
    categories,
    categoriesWithSessions,
  }
}

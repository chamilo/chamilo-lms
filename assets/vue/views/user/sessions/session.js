import { ref } from "vue"
import { useSecurityStore } from "../../../store/securityStore"
import sessionService from "../../../services/sessionService"
import isEmpty from "lodash/isEmpty"

export function useSession(type) {
  const securityStore = useSecurityStore()

  const isLoading = ref(false)
  const hasMore = ref(true)
  const errorMessage = ref("")
  const loadedCount = ref(0)
  const totalItems = ref(null)

  const uncategorizedSessions = ref([])
  const categories = ref([])
  const categoriesWithSessions = ref(new Map())

  // Avoid duplicates across pages
  const seenSessionIds = new Set()
  const seenCategoryIds = new Set()

  // Start with a higher page size to reduce round-trips (faster perceived load)
  // Keep it reasonable to avoid huge payloads.
  let paginationParams = {
    page: 1,
    itemsPerPage: 20,
  }

  function ingestSessions(items) {
    for (const session of items || []) {
      const sessionId = session["@id"] || session.id
      if (sessionId && seenSessionIds.has(sessionId)) {
        continue
      }
      if (sessionId) {
        seenSessionIds.add(sessionId)
      }

      loadedCount.value += 1

      const cat = session.category
      if (isEmpty(cat)) {
        uncategorizedSessions.value.push(session)
        continue
      }

      const catId = cat["@id"] || cat.id
      if (!catId) {
        uncategorizedSessions.value.push(session)
        continue
      }

      if (!seenCategoryIds.has(catId)) {
        seenCategoryIds.add(catId)
        categories.value.push(cat)
        categoriesWithSessions.value.set(catId, { sessions: [] })
      }

      const bucket = categoriesWithSessions.value.get(catId)
      bucket.sessions.push(session)
    }
  }

  async function getSessions() {
    if (!securityStore.isAuthenticated) {
      hasMore.value = false
      return
    }

    if (isLoading.value) {
      return
    }

    if (paginationParams === null) {
      hasMore.value = false
      return
    }

    isLoading.value = true
    errorMessage.value = ""

    try {
      const {
        items,
        nextPageParams,
        totalItems: apiTotal,
      } = await sessionService.findUserSubscriptions(securityStore.user["@id"], type, {
        ...paginationParams,
      })

      // totalItems is useful for "Showing X of Y"
      if (typeof apiTotal === "number") {
        totalItems.value = apiTotal
      } else if (apiTotal != null) {
        const parsed = parseInt(apiTotal, 10)
        totalItems.value = Number.isFinite(parsed) ? parsed : totalItems.value
      }

      paginationParams = nextPageParams ? { ...nextPageParams } : null
      hasMore.value = paginationParams !== null

      ingestSessions(items || [])
    } catch (e) {
      errorMessage.value = "Could not load sessions. Please try again."
      // Do not kill pagination on error; allow retry
    } finally {
      isLoading.value = false
    }
  }

  async function reload() {
    await getSessions()
  }

  getSessions().then(() => {})

  return {
    isLoading,
    reload,
    uncategorizedSessions,
    categories,
    categoriesWithSessions,
    hasMore,
    loadedCount,
    totalItems,
    errorMessage,
  }
}

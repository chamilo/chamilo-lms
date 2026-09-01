import { defineStore } from "pinia"
import { ref } from "vue"
import adminService from "../services/adminService"

// The legacy admin.ajax.php actions behind these three loaders (version/support/news) each make an
// external HTTP call to version.chamilo.org, so avoid re-hitting them every time AdminIndex.vue remounts.
const CACHE_TTL_MS = 60000

function createCachedLoader(fetcher, targetRef) {
  let lastFetchedAt = 0
  let pendingRequest = null

  return async function load({ force = false, onFetchStart } = {}) {
    const isFresh = !force && undefined !== targetRef.value && Date.now() - lastFetchedAt < CACHE_TTL_MS

    if (isFresh) {
      return
    }

    if (!pendingRequest) {
      onFetchStart?.()
      lastFetchedAt = Date.now()
      pendingRequest = fetcher()
        .then((data) => {
          targetRef.value = data
        })
        .finally(() => {
          pendingRequest = null
        })
    }

    await pendingRequest
  }
}

export const useAdminIndexBlocksStore = defineStore("adminIndexBlocksStore", () => {
  const blockVersionStatusEl = ref()
  const blockNewsStatusEl = ref()
  const blockSupportStatusEl = ref()

  const loadVersion = createCachedLoader(adminService.findVersion, blockVersionStatusEl)
  const loadNews = createCachedLoader(adminService.findAnnouncements, blockNewsStatusEl)
  const loadSupport = createCachedLoader(adminService.findSupport, blockSupportStatusEl)

  return {
    blockVersionStatusEl,
    blockNewsStatusEl,
    blockSupportStatusEl,
    loadVersion,
    loadNews,
    loadSupport,
  }
})

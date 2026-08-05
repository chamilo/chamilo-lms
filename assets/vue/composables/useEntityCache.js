import { ref } from "vue"
import baseService from "../services/baseService"

const caches = new Map()

function createCache(endpoint, requestParams, labelField) {
  const items = ref([])
  const loaded = ref(false)
  const loading = ref(false)
  let loadPromise = null

  /**
   * Fetch the collection once per session. Concurrent calls share the same promise;
   * later calls return the cached items.
   * @returns {Promise<Object[]>}
   */
  async function load() {
    if (loaded.value) {
      return items.value
    }

    if (loadPromise) {
      return loadPromise
    }

    loading.value = true

    loadPromise = baseService
      .getCollection(endpoint, requestParams)
      .then((res) => {
        items.value = res.items ?? []
        loaded.value = true

        return items.value
      })
      .finally(() => {
        loading.value = false
        loadPromise = null
      })

    return loadPromise
  }

  /**
   * Filter the cached items in memory by the configured label field.
   * Empty queries return the full list. Designed to be passed as the `search` prop of BaseAutocomplete.
   * @param {string} query
   * @returns {Object[]}
   */
  function search(query) {
    const q = String(query ?? "")
      .toLowerCase()
      .trim()

    if (!q) {
      return items.value
    }

    return items.value.filter((item) =>
      String(item[labelField] ?? "")
        .toLowerCase()
        .includes(q),
    )
  }

  /**
   * Resolve a numeric id to its full cached object. Useful in edit mode when the
   * backend payload only holds the id and BaseAutocomplete expects the full object.
   * @param {number|string|null} id
   * @returns {Object|null}
   */
  function findById(id) {
    if (id === null || id === undefined || id === "") {
      return null
    }

    const numericId = Number(id)

    return items.value.find((i) => i.id === numericId) ?? null
  }

  /**
   * Clear the cached items and reset the loaded flag so the next `load()` refetches.
   * Call after a create / update / delete on the underlying entity.
   * @returns {void}
   */
  function invalidate() {
    items.value = []
    loaded.value = false
  }

  return { items, loading, loaded, load, search, findById, invalidate }
}

/**
 * Get (or create) a shared, module-level cache for a collection endpoint.
 * The same endpoint string returns the same cache instance across components and views.
 * @param {string} endpoint - API Platform collection IRI (e.g. "/api/skills").
 * @param {Object} [requestParams] - Query params forwarded to the GET request (e.g. `{ pagination: false }`).
 * @param {string} [labelField] - Property name used by `search()` to filter items. Defaults to "title".
 * @returns {{ items: import('vue').Ref<Object[]>, loading: import('vue').Ref<boolean>, loaded: import('vue').Ref<boolean>, load: () => Promise<Object[]>, search: (query: string) => Object[], findById: (id: number|string|null) => Object|null, invalidate: () => void }}
 */
export function useEntityCache(endpoint, requestParams = {}, labelField = "title") {
  if (!caches.has(endpoint)) {
    caches.set(endpoint, createCache(endpoint, requestParams, labelField))
  }

  return caches.get(endpoint)
}

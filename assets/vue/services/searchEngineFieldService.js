import baseService from "./baseService"

export default {
  /**
   * Lists the configured search engine fields (collection endpoint).
   * @returns {Promise<{totalItems: number, items: Object[], nextPageParams: Object|null}>}
   */
  async listFields() {
    return baseService.getCollection("/api/search_engine_fields")
  },

  /**
   * Lists the stored search engine field values for a resource node.
   * Tries the IRI-based filter first, then falls back to the raw id filter.
   * @param {number|string} resourceNodeId
   * @returns {Promise<Object[]>}
   */
  async listFieldValues(resourceNodeId) {
    const iri = `/api/resource_nodes/${resourceNodeId}`
    const attempts = [
      { resourceNode: iri, pagination: false },
      { resourceNodeId, pagination: false },
    ]

    for (const params of attempts) {
      try {
        const { items } = await baseService.getCollection("/api/search_engine_field_values", params)
        if (Array.isArray(items)) {
          return items
        }
      } catch (error) {
        console.warn("[Search] Field values request error:", error)
      }
    }

    return []
  },
}

import axios from "axios"

export default {
  /**
   * @param {string} searchTerm
   * @returns {Promise<Object>} { totalItems, items }
   */
  search: async (searchTerm) => {
    const response = {}

    try {
      const { data } = await axios.get("/api/usergroups/search", {
        params: { search: searchTerm },
      })

      response.totalItems = data["hydra:totalItems"]
      response.items = data["hydra:member"]
    } catch {
      response.totalItems = 0
      response.items = []
    }

    return response
  },
}

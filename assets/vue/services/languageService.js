import makeService from "./api"
import baseService from "./baseService"

const languageExtensions = {
  async findAllAvailable() {
    try {
      return await baseService.get("/api/languages", { available: true })
    } catch (error) {
      console.error("Error fetching available languages:", error)
      throw error
    }
  },

  /**
   * Searches languages by isocode (includes unavailable ones).
   * @param {string} isocode
   * @returns {Promise<{totalItems, items}>}
   */
  async searchByIsocode(isocode) {
    return baseService.getCollection("/api/languages", { isocode })
  },
}
export default makeService("languages", languageExtensions)

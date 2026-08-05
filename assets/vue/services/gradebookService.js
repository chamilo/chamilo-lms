import baseService from "./baseService"

const API_BASE = "/gradebook"

export default {
  /**
   * Fetches gradebook categories for a specific course and session.
   * @param {number} courseId The course ID.
   * @param {number|null} sessionId The session ID (optional).
   * @returns {Promise<Array>} The list of gradebook categories.
   */
  async getCategories(courseId, sessionId = null) {
    const params = { courseId }
    if (sessionId) params.sessionId = sessionId

    try {
      return await baseService.get(`${API_BASE}/categories`, params)
    } catch (error) {
      console.error("Error fetching gradebook categories:", error)
      throw error
    }
  },

  /**
   * Sets a document as the default certificate for a course.
   * @param {number|string} courseId
   * @param {number|string} certificateId
   * @returns {Promise<Object>}
   */
  async setDefaultCertificate(courseId, certificateId) {
    return await baseService.patch(`${API_BASE}/set_default_certificate/${courseId}/${certificateId}`, {})
  },

  /**
   * Fetches the default certificate for a course.
   * @param {number|string} courseId
   * @returns {Promise<Object>}
   */
  async getDefaultCertificate(courseId) {
    return await baseService.get(`${API_BASE}/default_certificate/${courseId}`)
  },
}

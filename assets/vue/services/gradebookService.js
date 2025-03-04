import axios from "axios"

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
      const response = await axios.get(`${API_BASE}/categories`, { params })
      return response.data
    } catch (error) {
      console.error("Error fetching gradebook categories:", error)
      throw error
    }
  },
}

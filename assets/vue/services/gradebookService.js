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

  /**
   * Updates the calculation mode (weighted_average | points_sum) of a gradebook category.
   * @param {number|string} categoryId The numeric id of the gradebook category.
   * @param {string} calculationMode The target calculation mode.
   * @returns {Promise<Object>} The updated category resource.
   */
  async updateCalculationMode(categoryId, calculationMode) {
    return await baseService.put(`/api/gradebook_categories/${categoryId}`, { calculationMode })
  },

  /**
   * Fetches the gradebook links of a category.
   * @param {number|string} categoryId The numeric id of the gradebook category.
   * @returns {Promise<Array>} The list of gradebook links.
   */
  async getLinks(categoryId) {
    return await baseService.getCollection("/api/gradebook_links", {
      category: `/api/gradebook_categories/${categoryId}`,
    })
  },

  /**
   * Creates a forum participation gradebook item.
   * @param {Object} payload The link payload.
   * @param {number} payload.threadId The forum thread id used as ref_id.
   * @param {number} payload.courseId The course id.
   * @param {number|string} payload.categoryId The gradebook category id.
   * @param {number} payload.pointsOne Points awarded for exactly one message.
   * @param {number} payload.pointsMany Points awarded for two or more messages.
   * @returns {Promise<Object>} The created gradebook link resource.
   */
  async createForumParticipationLink({ threadId, courseId, categoryId, pointsOne, pointsMany }) {
    // 11 = LINK_FORUM_PARTICIPATION. Weight equals pointsMany (the item's max points) so in
    // points_sum the contribution equals the earned points.
    return await baseService.post("/api/gradebook_links", {
      type: 11,
      refId: threadId,
      course: `/api/courses/${courseId}`,
      category: `/api/gradebook_categories/${categoryId}`,
      weight: Number(pointsMany),
      pointsOne: String(pointsOne),
      pointsMany: String(pointsMany),
    })
  },

  /**
   * Updates a forum participation gradebook item.
   * @param {number|string} linkId The numeric id of the gradebook link.
   * @param {Object} payload The fields to update (pointsOne, pointsMany, refId).
   * @returns {Promise<Object>} The updated gradebook link resource.
   */
  async updateForumParticipationLink(linkId, payload) {
    return await baseService.put(`/api/gradebook_links/${linkId}`, payload)
  },

  /**
   * Deletes a gradebook link.
   * @param {number|string} linkId The numeric id of the gradebook link.
   * @returns {Promise<Object>}
   */
  async deleteLink(linkId) {
    return await baseService.delete(`/api/gradebook_links/${linkId}`)
  },
}

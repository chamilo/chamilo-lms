import baseService from "./baseService"

const API_BASE = "/api/transcripts"
// Transcripts belong to a user, not to a course: never send the course context.
const requestConfig = { skipCourseContext: true }

export default {
  /**
   * Fetches the transcript of a user with their official transcripts and what the viewer can do.
   * @param {number|null} userId Target user, or null for the authenticated user.
   * @returns {Promise<Object>} Transcript overview.
   */
  async getOverview(userId = null) {
    return await baseService.get(`${API_BASE}/overview`, userId ? { userId } : {}, requestConfig)
  },

  /**
   * Issues a signed official transcript of a user.
   * @param {number} userId Target user.
   * @returns {Promise<Object>} The issued transcript.
   */
  async issue(userId) {
    return await baseService.post(API_BASE, { userId }, {}, requestConfig)
  },

  /**
   * @param {number} transcriptId Official transcript to revoke.
   * @returns {Promise<Object>} The revoked transcript.
   */
  async revoke(transcriptId) {
    return await baseService.post(`${API_BASE}/${transcriptId}/revoke`, {}, {}, requestConfig)
  },

  /**
   * Uploads the signature image printed on the official transcripts of the access URL.
   * @param {File} file PNG or JPEG image.
   * @returns {Promise<Object>} The new signature image as a data URI.
   */
  async uploadSignatureImage(file) {
    const formData = new FormData()
    formData.append("file", file)

    return await baseService.postForm(`${API_BASE}/signature-image`, formData)
  },

  /**
   * @returns {Promise<Object>} An empty signature image.
   */
  async deleteSignatureImage() {
    return await baseService.delete(`${API_BASE}/signature-image`, requestConfig)
  },
}

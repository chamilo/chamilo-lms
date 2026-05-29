import baseService from "./baseService"

export default {
  /**
   * Fetches the AI generation capabilities for the current context.
   * @param {Object} [params={}]
   * @returns {Promise<Object>}
   */
  async getCapabilities(params = {}) {
    return baseService.get("/ai/capabilities", params)
  },

  /**
   * Fetches the available AI text providers.
   * @returns {Promise<Object>}
   */
  async getTextProviders() {
    return baseService.get("/ai/text_providers")
  },

  /**
   * Requests AI feedback for a document.
   * @param {Object} payload
   * @returns {Promise<Object>}
   */
  async getDocumentFeedback(payload) {
    return baseService.post("/ai/document_feedback", payload)
  },

  /**
   * Saves the AI document feedback answer to the user inbox.
   * @param {Object} payload
   * @returns {Promise<Object>}
   */
  async saveDocumentFeedbackToInbox(payload) {
    return baseService.post("/ai/document_feedback/save_to_inbox", payload)
  },

  /**
   * Triggers an AI media generation request (image or video).
   * @param {string} endpoint - e.g. "/ai/generate_image" or "/ai/generate_video"
   * @param {Object} payload
   * @returns {Promise<Object>}
   */
  async generateMedia(endpoint, payload) {
    return baseService.post(endpoint, payload)
  },

  /**
   * Polls a single AI video generation job.
   * @param {string} jobId
   * @param {string|null} [providerCode]
   * @returns {Promise<Object>}
   */
  async getVideoJob(jobId, providerCode = null) {
    return baseService.get(`/ai/video_job/${encodeURIComponent(jobId)}`, { ai_provider: providerCode || null })
  },
}

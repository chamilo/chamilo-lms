import baseService from "./baseService"

const API_BASE = "/gradebook"

export default {
  /**
   * Builds a secure Gradebook download URL for a server-generated export.
   * @param {string} scope Export scope: flat, evaluation, learner or students.
   * @param {string} format Export format.
   * @param {Object} params Course context and scope-specific identifiers.
   * @returns {string} Export URL.
   */
  buildExportUrl(scope, format, params = {}) {
    const query = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined || value === "") {
        return
      }
      query.set(key, String(value))
    })

    const queryString = query.toString()
    return `/api/gradebook/export/${encodeURIComponent(scope)}.${encodeURIComponent(format)}${queryString ? `?${queryString}` : ""}`
  },

  /**
   * Fetches the read-only Gradebook overview for the current course context.
   * @param {Object} params Course context and optional categoryId.
   * @returns {Promise<Object>} Gradebook overview data.
   */
  async getOverview(params) {
    return await baseService.get("/api/gradebook/overview", params)
  },

  /**
   * Fetches the change history of one Gradebook evaluation or online activity link.
   * @param {Object} params Course context, kind and itemId.
   * @returns {Promise<Object>} Gradebook history data.
   */
  async getHistory(params) {
    return await baseService.get("/api/gradebook/history", params)
  },

  /**
   * Fetches the graphical score distribution for the selected Gradebook category.
   * @param {Object} params Course context and optional categoryId.
   * @returns {Promise<Object>} Gradebook graph data.
   */
  async getGraph(params) {
    return await baseService.get("/api/gradebook/graph", params)
  },

  /**
   * Fetches Gradebook skill-item validation data for one learner.
   * @param {Object} params Course context and learner userId.
   * @returns {Promise<Object>} Learner skill validation data.
   */
  async getLearnerSkills(params) {
    return await baseService.get("/api/gradebook/learner-skills", params)
  },

  /**
   * Toggles the acquired state of one learner skill.
   * @param {Object} payload Skill action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async toggleLearnerSkill(payload, params) {
    return await baseService.post("/api/gradebook/learner-skills/action", payload, {}, { params })
  },

  /**
   * Fetches OpenBadges assertions for one learner.
   * @param {Object} params Course context and optional userId.
   * @returns {Promise<Object>} Badge export data.
   */
  async getBadges(params) {
    return await baseService.get("/api/gradebook/badges", params)
  },

  /**
   * Searches public Gradebook certificates.
   * @param {Object} params Search filters.
   * @returns {Promise<Object>} Certificate search result.
   */
  async searchCertificates(params = {}) {
    return await baseService.get("/api/gradebook/certificate-search", params)
  },

  /**
   * Fetches certificates achieved by the authenticated user.
   * @returns {Promise<Object>} User certificate lists.
   */
  async getMyCertificates() {
    return await baseService.get("/api/gradebook/my-certificates")
  },

  /**
   * Synchronizes skills and certificate generation for the current learner.
   * @param {Object} payload Achievement synchronization payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Synchronization result.
   */
  async syncAchievements(payload, params) {
    return await baseService.post("/api/gradebook/achievements/sync", payload, {}, { params })
  },

  /**
   * Fetches advanced category options such as Grade Models and skills.
   * @param {Object} params Course context and category filters.
   * @returns {Promise<Object>} Advanced Gradebook settings.
   */
  async getAdvancedSettings(params) {
    return await baseService.get("/api/gradebook/advanced-settings", params)
  },

  /**
   * Fetches the read-only Gradebook learner score matrix.
   * @param {Object} params Course context, category and pagination filters.
   * @returns {Promise<Object>} Gradebook learner report data.
   */
  async getReport(params) {
    return await baseService.get("/api/gradebook/report", params)
  },

  /**
   * Fetches a detailed Gradebook report for one learner.
   * @param {Object} params Course context, category and optional userId.
   * @returns {Promise<Object>} Detailed learner report.
   */
  async getLearnerReport(params) {
    return await baseService.get("/api/gradebook/learner-report", params)
  },

  /**
   * Creates or updates a Gradebook comment for one learner.
   * @param {Object} payload Comment action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async saveComment(payload, params) {
    return await baseService.post("/api/gradebook/comments/action", payload, {}, { params })
  },

  /**
   * Fetches item weights for the selected Gradebook category.
   * @param {Object} params Course context and optional categoryId.
   * @returns {Promise<Object>} Gradebook weights data.
   */
  async getWeights(params) {
    return await baseService.get("/api/gradebook/weights", params)
  },

  /**
   * Updates or automatically distributes Gradebook item weights.
   * @param {Object} payload Weight action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runWeightAction(payload, params) {
    return await baseService.post("/api/gradebook/weights/action", payload, {}, { params })
  },

  /**
   * Fetches score display settings for the selected Gradebook category.
   * @param {Object} params Course context and optional categoryId.
   * @returns {Promise<Object>} Scoring settings.
   */
  async getScoringSettings(params) {
    return await baseService.get("/api/gradebook/scoring-settings", params)
  },

  /**
   * Updates score display settings for the selected Gradebook category.
   * @param {Object} payload Scoring settings payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runScoringAction(payload, params) {
    return await baseService.post("/api/gradebook/scoring-settings/action", payload, {}, { params })
  },

  /**
   * Fetches custom score distribution statistics for a manual evaluation.
   * @param {Object} params Course context and evaluationId.
   * @returns {Promise<Object>} Evaluation statistics.
   */
  async getEvaluationStatistics(params) {
    return await baseService.get("/api/gradebook/evaluation-statistics", params)
  },

  /**
   * Runs a Gradebook category action in the current course context.
   * @param {Object} payload Category action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runCategoryAction(payload, params) {
    return await baseService.post("/api/gradebook/categories/action", payload, {}, { params })
  },

  /**
   * Runs a manual Gradebook evaluation action.
   * @param {Object} payload Evaluation action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runEvaluationAction(payload, params) {
    return await baseService.post("/api/gradebook/evaluations/action", payload, {}, { params })
  },

  /**
   * Fetches learner results for a manual Gradebook evaluation.
   * @param {Object} params Course context and evaluationId.
   * @returns {Promise<Object>} Evaluation results data.
   */
  async getEvaluationResults(params) {
    return await baseService.get("/api/gradebook/evaluation-results", params)
  },

  /**
   * Imports manual Gradebook evaluation results from a CSV file.
   * @param {FormData} formData Import form data.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Import result.
   */
  async importEvaluationResults(formData, params) {
    return await baseService.post("/api/gradebook/evaluation-results/import", formData, {}, { params })
  },

  /**
   * Runs a manual Gradebook evaluation result action.
   * @param {Object} payload Result action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runEvaluationResultAction(payload, params) {
    return await baseService.post("/api/gradebook/evaluation-results/action", payload, {}, { params })
  },

  /**
   * Fetches available resources for a Gradebook online activity link.
   * @param {Object} params Course context and optional categoryId/linkId.
   * @returns {Promise<Object>} Online activity options.
   */
  async getLinkOptions(params) {
    return await baseService.get("/api/gradebook/link-options", params)
  },

  /**
   * Runs a Gradebook online activity action.
   * @param {Object} payload Online activity action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runLinkAction(payload, params) {
    return await baseService.post("/api/gradebook/links/action", payload, {}, { params })
  },

  /**
   * Fetches Gradebook certificates for the current course context.
   * @param {Object} params Course context and optional category/official-code filters.
   * @returns {Promise<Object>} Certificate management data.
   */
  async getCertificates(params) {
    return await baseService.get("/api/gradebook/certificates", params)
  },

  /**
   * Generates or deletes Gradebook certificates in the current course context.
   * @param {Object} payload Certificate action payload.
   * @param {Object} params Course context.
   * @returns {Promise<Object>} Action result.
   */
  async runCertificateAction(payload, params) {
    return await baseService.post("/api/gradebook/certificates/action", payload, {}, { params })
  },

  /**
   * Builds the PDF export URL for generated Gradebook certificates.
   * @param {Object} params Course context and optional category/official-code filters.
   * @returns {string} Certificate PDF export URL.
   */
  buildCertificateExportUrl(params = {}) {
    const query = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined || value === "") {
        return
      }
      query.set(key, String(value))
    })

    const queryString = query.toString()
    return `/api/gradebook/certificates/export.pdf${queryString ? `?${queryString}` : ""}`
  },

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


}

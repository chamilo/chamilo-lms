import makeService from "./api"
import baseService from "./baseService"

// we should refactor this to use methods in export default using baseService
// see assets/vue/services/api.js for reference
const oldService = makeService("documents")

export default {
  ...oldService,

  /**
   * Retrieves all document templates for a given course.
   *
   * @param {string} courseId - The ID of the course.
   * @returns {Promise}
   */
  getTemplates: async (courseId) => {
    return baseService.get(`/template/all-templates/${courseId}`)
  },
}

import baseService from "./baseService"

export default {
  /**
   * Fetches the platform configuration list.
   * @returns {Promise<Object>}
   */
  list() {
    return baseService.get("/platform-config/list")
  },

  /**
   * Fetches the course settings configuration list.
   * @param {Object} [params={}]
   * @returns {Promise<Object>}
   */
  listCourseSettings(params = {}) {
    return baseService.get("/platform-config/list/course_settings", params)
  },
}

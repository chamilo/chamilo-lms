import baseService from "./baseService"

/**
 * Admin "Multi URLs" dashboard data endpoint, served by the admin controllers
 * under /admin/* (not API Platform). Read-only.
 */
export default {
  /**
   * @returns {Promise<Object>}
   */
  list() {
    return baseService.get("/admin/urls-data")
  },

  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  listUsers(params) {
    return baseService.get("/admin/urls-data/users", params)
  },

  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  listCourses(params) {
    return baseService.get("/admin/urls-data/courses", params)
  },

  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  getLogins(params) {
    return baseService.get("/admin/urls-data/logins", params)
  },

  /**
   * @param {number|string} id
   * @param {Array<{courseId: number, sessionId: number}>} pairs
   * @returns {Promise<Object>}
   */
  getUserUrls(id, pairs) {
    return baseService.get(`/admin/urls-data/users/${id}/urls`, {
      pairs: pairs.map((pair) => `${pair.courseId}:${pair.sessionId}`).join(","),
    })
  },

  /**
   * @param {number|string} id
   * @returns {Promise<Object>}
   */
  getCourseDetail(id) {
    return baseService.get(`/admin/urls-data/courses/${id}/detail`)
  },
}

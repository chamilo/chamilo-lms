import baseService from "./baseService"

/**
 * Data source for the Access URL management pages (URL CRUD, and
 * user/course/user group/course category assignment), served by the
 * admin controllers under /admin/access-urls-*-data (not API Platform).
 */
export default {
  /**
   * @returns {Promise<Object>}
   */
  list() {
    return baseService.get("/admin/access-urls-manage-data")
  },

  /**
   * @param {Object} payload
   * @returns {Promise<Object>}
   */
  create(payload) {
    return baseService.post("/admin/access-urls-manage-data", payload)
  },

  /**
   * @param {number} id
   * @param {Object} payload
   * @returns {Promise<Object>}
   */
  update(id, payload) {
    return baseService.put(`/admin/access-urls-manage-data/${id}`, payload)
  },

  /**
   * @param {number} id
   * @param {string} token
   * @returns {Promise<Object>}
   */
  lock(id, token) {
    return baseService.post(`/admin/access-urls-manage-data/${id}/lock`, { _token: token })
  },

  /**
   * @param {number} id
   * @param {string} token
   * @returns {Promise<Object>}
   */
  unlock(id, token) {
    return baseService.post(`/admin/access-urls-manage-data/${id}/unlock`, { _token: token })
  },

  /**
   * @param {string} token
   * @returns {Promise<Object>}
   */
  registerAdmin(token) {
    return baseService.post("/admin/access-urls-manage-data/register-admin", { _token: token })
  },

  /**
   * @param {number} accessUrlId
   * @returns {Promise<Object>}
   */
  listUsers(accessUrlId) {
    return baseService.get("/admin/access-urls-users-data", { access_url_id: accessUrlId })
  },

  /**
   * @param {Object} payload {access_url_id, user_ids, _token}
   * @returns {Promise<Object>}
   */
  assignUsers(payload) {
    return baseService.post("/admin/access-urls-users-data", payload)
  },

  /**
   * @param {Object} payload {user_ids, url_ids, action, _token}
   * @returns {Promise<Object>}
   */
  bulkUsers(payload) {
    return baseService.post("/admin/access-urls-users-data/bulk", payload)
  },

  /**
   * @param {number} accessUrlId
   * @returns {Promise<Object>}
   */
  listCourses(accessUrlId) {
    return baseService.get("/admin/access-urls-courses-data", { access_url_id: accessUrlId })
  },

  /**
   * @param {Object} payload {access_url_id, course_ids, _token}
   * @returns {Promise<Object>}
   */
  assignCourses(payload) {
    return baseService.post("/admin/access-urls-courses-data", payload)
  },

  /**
   * @param {Object} payload {course_ids, url_ids, action, _token}
   * @returns {Promise<Object>}
   */
  bulkCourses(payload) {
    return baseService.post("/admin/access-urls-courses-data/bulk", payload)
  },

  /**
   * @param {number} accessUrlId
   * @returns {Promise<Object>}
   */
  listUserGroups(accessUrlId) {
    return baseService.get("/admin/access-urls-usergroups-data", { access_url_id: accessUrlId })
  },

  /**
   * @param {Object} payload {access_url_id, group_ids, _token}
   * @returns {Promise<Object>}
   */
  assignUserGroups(payload) {
    return baseService.post("/admin/access-urls-usergroups-data", payload)
  },

  /**
   * @param {Object} payload {group_ids, url_ids, action, _token}
   * @returns {Promise<Object>}
   */
  bulkUserGroups(payload) {
    return baseService.post("/admin/access-urls-usergroups-data/bulk", payload)
  },

  /**
   * @param {number} accessUrlId
   * @returns {Promise<Object>}
   */
  listCourseCategories(accessUrlId) {
    return baseService.get("/admin/access-urls-course-categories-data", { access_url_id: accessUrlId })
  },

  /**
   * @param {Object} payload {access_url_id, category_ids, _token}
   * @returns {Promise<Object>}
   */
  assignCourseCategories(payload) {
    return baseService.post("/admin/access-urls-course-categories-data", payload)
  },
}

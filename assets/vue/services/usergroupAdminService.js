import baseService from "./baseService"

/**
 * Admin usergroup (classes) data endpoints, served by the admin controllers
 * under /admin/* (not API Platform). All requests go through baseService.
 */
export default {
  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  list(params) {
    return baseService.get("/admin/usergroups-data", params)
  },

  /**
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  create(formData) {
    return baseService.post("/admin/usergroups-data", formData)
  },

  /**
   * @param {number|string} id
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  update(id, formData) {
    return baseService.post(`/admin/usergroups-data/${id}`, formData)
  },

  /**
   * @param {number|string} id
   * @param {string} csrfToken
   * @returns {Promise<any>}
   */
  remove(id, csrfToken) {
    return baseService.delete(`/admin/usergroups-data/${id}`, { headers: { "X-CSRF-Token": csrfToken } })
  },

  /**
   * @param {number|string} id
   * @returns {Promise<Object>}
   */
  preview(id) {
    return baseService.get(`/admin/usergroups-data/${id}/preview`)
  },

  /**
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  importClasses(formData) {
    return baseService.post("/admin/usergroup-import-data", formData)
  },

  /**
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  importUserLinks(formData) {
    return baseService.post("/admin/usergroup-user-import-data", formData)
  },

  /**
   * @param {number|string} groupId
   * @returns {Promise<Object>}
   */
  listUsers(groupId) {
    return baseService.get(`/admin/usergroup-users-data/${groupId}`)
  },

  /**
   * @param {number|string} groupId
   * @param {number|string} userId
   * @param {string} csrfToken
   * @returns {Promise<any>}
   */
  removeUser(groupId, userId, csrfToken) {
    return baseService.delete(`/admin/usergroup-users-data/${groupId}/user/${userId}`, {
      headers: { "X-CSRF-Token": csrfToken },
    })
  },

  /**
   * @param {number|string} groupId
   * @param {string|number} relation
   * @returns {Promise<Object>}
   */
  getAddUsersData(groupId, relation) {
    return baseService.get(`/admin/usergroups/${groupId}/add-users-data`, { relation })
  },

  /**
   * @param {number|string} groupId
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  saveUsers(groupId, formData) {
    return baseService.post(`/admin/usergroups/${groupId}/add-users-data`, formData)
  },

  /**
   * @param {number|string} groupId
   * @returns {Promise<Object>}
   */
  getCoursesData(groupId) {
    return baseService.get(`/admin/usergroup-courses-data/${groupId}`)
  },

  /**
   * @param {number|string} groupId
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  saveCourses(groupId, formData) {
    return baseService.post(`/admin/usergroup-courses-data/${groupId}`, formData)
  },

  /**
   * @param {number|string} groupId
   * @returns {Promise<Object>}
   */
  getSessionsData(groupId) {
    return baseService.get(`/admin/usergroup-sessions-data/${groupId}`)
  },

  /**
   * @param {number|string} groupId
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  saveSessions(groupId, formData) {
    return baseService.post(`/admin/usergroup-sessions-data/${groupId}`, formData)
  },
}

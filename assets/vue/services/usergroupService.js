import baseService from "./baseService"

export default {
  /**
   * @param {string} searchTerm
   * @returns {Promise<{totalItems, items}>}
   */
  search: async (searchTerm) => {
    return await baseService.getCollection("/api/usergroups/search", {
      search: searchTerm,
    })
  },

  /**
   * @param {number|string} groupId
   * @returns {Promise<{totalItems, items}>}
   */
  getMembers: async (groupId) => {
    return await baseService.getCollection(`/api/usergroups/${groupId}/members`)
  },

  /**
   * @param {number|string} groupId
   * @returns {Promise<{totalItems, items}>}
   */
  getMessages: async (groupId) => {
    return await baseService.getCollection(`/api/usergroups/${groupId}/messages`)
  },

  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async createGroup(params) {
    return await baseService.post("/api/usergroups", params)
  },

  /**
   * @param {number} groupId
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async updateGroup(groupId, params) {
    return await baseService.put(`/api/usergroups/${groupId}`, params)
  },

  /**
   * @param {number} groupId
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async uploadPicture(groupId, params) {
    return await baseService.postForm(`/social-network/upload-group-picture/${groupId}`, params)
  },

  /**
   * @returns {Promise<Array>}
   */
  async listNewest() {
    const { items } = await baseService.getCollection("/api/usergroup/list/newest")

    return items
  },

  /**
   * @returns {Promise<Array>}
   */
  async listPopular() {
    const { items } = await baseService.getCollection("/api/usergroup/list/popular")

    return items
  },

  /**
   * @returns {Promise<Array>}
   */
  async listMine() {
    const { items } = await baseService.getCollection("/api/usergroup/list/my")

    return items
  },
}

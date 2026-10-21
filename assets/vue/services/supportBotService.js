import baseService from "./baseService"

function requestConfig() {
  return { skipCourseContext: true }
}

export default {
  async getTrees() {
    return await baseService.get("/api/support-bot/trees", {}, requestConfig())
  },

  async getConfiguration() {
    return await baseService.get("/api/support-bot/trees/configuration", {}, requestConfig())
  },

  async getTree(treeId) {
    return await baseService.get(`/api/support-bot/trees/${treeId}`, {}, requestConfig())
  },

  async saveTree(payload, treeId = null) {
    if (treeId) {
      return await baseService.patch(`/api/support-bot/admin/trees/${treeId}`, payload, requestConfig())
    }

    return await baseService.post("/api/support-bot/admin/trees", payload, {}, requestConfig())
  },

  async publishTree(treeId) {
    return await baseService.post(`/api/support-bot/admin/trees/${treeId}/publish`, {}, {}, requestConfig())
  },

  async archiveTree(treeId) {
    return await baseService.post(`/api/support-bot/admin/trees/${treeId}/archive`, {}, {}, requestConfig())
  },

  async duplicateTree(treeId) {
    return await baseService.post(`/api/support-bot/admin/trees/${treeId}/duplicate`, {}, {}, requestConfig())
  },

  async deleteTree(treeId) {
    return await baseService.delete(`/api/support-bot/admin/trees/${treeId}`, requestConfig())
  },

  async getRuntimeContext() {
    return await baseService.get("/api/support-bot/runtime", {}, requestConfig())
  },

  async startConversation() {
    return await baseService.post("/api/support-bot/runtime/start", {}, {}, requestConfig())
  },

  async advanceConversation(conversationId, optionId) {
    return await baseService.post(
      `/api/support-bot/runtime/${conversationId}/advance`,
      { optionId: Number(optionId) },
      {},
      requestConfig(),
    )
  },

  async getConversations(treeId, params = {}) {
    return await baseService.get(`/api/support-bot/trees/${treeId}/conversations`, params, requestConfig())
  },

  async getConversation(treeId, conversationId) {
    return await baseService.get(
      `/api/support-bot/trees/${treeId}/conversations/${conversationId}`,
      {},
      requestConfig(),
    )
  },

  async importTree(file) {
    const formData = new FormData()
    formData.append("file", file)
    return await baseService.post("/api/support-bot/admin/trees/import", formData, {}, requestConfig())
  },

  exportTreeUrl(treeId) {
    return `/api/support-bot/admin/trees/${treeId}/export`
  },
}

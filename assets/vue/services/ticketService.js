import baseService from "./baseService"

export default {
  async getList(params = {}) {
    return await baseService.get("/api/ticket/list", params, { skipCourseContext: true })
  },

  async getForm(params = {}) {
    return await baseService.get("/api/ticket/form", params, { skipCourseContext: true })
  },

  async getDetail(ticketId) {
    return await baseService.get(`/api/ticket/${ticketId}`, {}, { skipCourseContext: true })
  },

  async create(formData) {
    return await baseService.post("/api/ticket/create", formData, {}, { skipCourseContext: true })
  },

  async reply(ticketId, formData) {
    return await baseService.post(`/api/ticket/${ticketId}/reply`, formData, {}, { skipCourseContext: true })
  },

  async subscribe(ticketId, csrfToken) {
    return await baseService.post(`/api/ticket/${ticketId}/subscribe`, { csrfToken }, {}, { skipCourseContext: true })
  },

  async unsubscribe(ticketId, csrfToken) {
    return await baseService.post(`/api/ticket/${ticketId}/unsubscribe`, { csrfToken }, {}, { skipCourseContext: true })
  },

  async close(ticketId, csrfToken) {
    return await baseService.post(`/api/ticket/${ticketId}/close`, { csrfToken }, {}, { skipCourseContext: true })
  },

  async respondToConfirmation(ticketId, confirmed, csrfToken) {
    return await baseService.post(
      `/api/ticket/${ticketId}/confirmation`,
      { confirmed, csrfToken },
      {},
      { skipCourseContext: true },
    )
  },

  async searchUsers(query = "") {
    return await baseService.get("/api/ticket/user-options", { query }, { skipCourseContext: true })
  },

  async getAdminConfiguration(params = {}) {
    return await baseService.get("/api/ticket/admin/configuration", params, { skipCourseContext: true })
  },

  async createAdminItem(section, projectId, payload) {
    const endpoint =
      section === "categories" ? `/api/ticket/admin/projects/${projectId}/categories` : `/api/ticket/admin/${section}`
    return await baseService.post(endpoint, payload, {}, { skipCourseContext: true })
  },

  async updateAdminItem(section, id, payload) {
    const endpoint =
      section === "categories" ? `/api/ticket/admin/categories/${id}` : `/api/ticket/admin/${section}/${id}`
    return await baseService.put(endpoint, payload, { skipCourseContext: true })
  },

  async deleteAdminItem(section, id, csrfToken) {
    const endpoint =
      section === "categories" ? `/api/ticket/admin/categories/${id}` : `/api/ticket/admin/${section}/${id}`
    return await baseService.delete(endpoint, {
      headers: { "X-CSRF-TOKEN": csrfToken },
      skipCourseContext: true,
    })
  },

  async updateCategoryUsers(categoryId, userIds, csrfToken) {
    return await baseService.put(
      `/api/ticket/admin/categories/${categoryId}/users`,
      { userIds, csrfToken },
      { skipCourseContext: true },
    )
  },

  async closeOldTickets(csrfToken) {
    return await baseService.post("/api/ticket/admin/close-old", { csrfToken }, {}, { skipCourseContext: true })
  },
}

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
    return await baseService.post(
      `/api/ticket/${ticketId}/subscribe`,
      { csrfToken },
      {},
      { skipCourseContext: true },
    )
  },

  async unsubscribe(ticketId, csrfToken) {
    return await baseService.post(
      `/api/ticket/${ticketId}/unsubscribe`,
      { csrfToken },
      {},
      { skipCourseContext: true },
    )
  },

  async close(ticketId, csrfToken) {
    return await baseService.post(`/api/ticket/${ticketId}/close`, { csrfToken }, {}, { skipCourseContext: true })
  },

  async searchUsers(query = "") {
    return await baseService.get("/api/ticket/user-options", { query }, { skipCourseContext: true })
  },
}

import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.getCollection("/api/course-invitations", cleanParams(params))
  },

  async getForm(params = {}) {
    return await baseService.get("/api/course-invitation/form", cleanParams(params))
  },

  async create(payload, params = {}) {
    return await baseService.post("/api/course-invitation", payload, {}, { params: cleanParams(params) })
  },

  async revoke(id, params = {}) {
    return await baseService.delete(`/api/course-invitation/${id}`, {
      params: cleanParams(params),
    })
  },
}

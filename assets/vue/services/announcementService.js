import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/announcement/list", cleanParams(params))
  },

  async getItem(id, params = {}) {
    return await baseService.get(`/api/announcement/${id}`, cleanParams(params))
  },

  async getForm(params = {}) {
    return await baseService.get("/api/announcement/form", cleanParams(params))
  },

  async preview(payload, params = {}) {
    return await baseService.post("/api/announcement/preview", payload, {}, { params: cleanParams(params) })
  },

  async create(payload, params = {}) {
    return await baseService.post("/api/announcement", payload, {}, { params: cleanParams(params) })
  },

  async update(id, payload, params = {}) {
    return await baseService.put(`/api/announcement/${id}`, payload, { params: cleanParams(params) })
  },
}

import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/course-description/list", params)
  },

  async getForm(params = {}) {
    return await baseService.get("/api/course-description/form", cleanParams(params))
  },

  async create(payload, params = {}) {
    return await baseService.post("/api/course-description", payload, {}, { params: cleanParams(params) })
  },

  async update(iid, payload, params = {}) {
    return await baseService.put(`/api/course-description/${iid}`, payload, { params: cleanParams(params) })
  },
}

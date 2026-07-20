import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/notebook/list", cleanParams(params))
  },

  async getForm(params = {}) {
    return await baseService.get("/api/notebook/form", cleanParams(params))
  },

  async create(payload, params = {}) {
    return await baseService.post("/api/notebook", payload, {}, { params: cleanParams(params) })
  },

  async update(iid, payload, params = {}) {
    return await baseService.put(`/api/notebook/${iid}`, payload, { params: cleanParams(params) })
  },

  async remove(iid, payload, params = {}) {
    return await baseService.delete(`/api/notebook/${iid}`, {
      params: cleanParams(params),
      data: payload,
    })
  },
}

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
}

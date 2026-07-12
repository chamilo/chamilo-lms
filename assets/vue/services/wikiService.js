import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getPage(params = {}) {
    return await baseService.get("/api/wiki/page", cleanParams(params))
  },
}

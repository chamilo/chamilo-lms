import baseService from "./baseService"

export default {
  async getList(params = {}) {
    return await baseService.get("/api/course-description/list", params)
  },
}

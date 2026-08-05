import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/course-classes/list", cleanParams(params))
  },

  async add(usergroupId, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-classes/actions/add",
      { usergroupId, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async remove(usergroupId, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-classes/actions/remove",
      { usergroupId, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async removeOnly(usergroupId, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-classes/actions/remove-only",
      { usergroupId, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async getMyClasses(params = {}) {
    return await baseService.get("/api/my-classes/list", cleanParams(params))
  },
}

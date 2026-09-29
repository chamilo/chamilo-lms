import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

function queryString(params = {}) {
  const query = new URLSearchParams(cleanParams(params)).toString()

  return query ? `?${query}` : ""
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/course-users/list", cleanParams(params))
  },

  async getAvailable(params = {}) {
    return await baseService.get("/api/course-users/available", cleanParams(params))
  },

  async subscribe(userIds, params = {}) {
    return await baseService.post(
      "/api/course-users/actions/subscribe",
      { userIds },
      {},
      { params: cleanParams(params) },
    )
  },

  async unsubscribe(userIds, params = {}) {
    return await baseService.post(
      "/api/course-users/actions/unsubscribe",
      { userIds },
      {},
      { params: cleanParams(params) },
    )
  },

  async setTutor(userId, tutor, params = {}) {
    return await baseService.post(
      "/api/course-users/actions/tutor",
      { userIds: [userId], tutor },
      {},
      { params: cleanParams(params) },
    )
  },

  async getImport(params = {}) {
    return await baseService.get("/api/course-users/import", cleanParams(params))
  },

  async importCsv(file, replace, params = {}) {
    const formData = new FormData()
    formData.append("file", file)
    formData.append("replace", replace ? "1" : "0")

    return await baseService.post("/api/course-users/import", formData, {}, { params: cleanParams(params) })
  },

  async exportFile(format, params = {}) {
    return await baseService.getRaw(`/api/course-users/export.${format}`, {
      params: cleanParams(params),
      responseType: "blob",
    })
  },

  buildListRouteQuery(type, currentQuery = {}) {
    return {
      ...currentQuery,
      type,
    }
  },

  buildLegacyUrl(path, params = {}) {
    return `${path}${queryString(params)}`
  },
}

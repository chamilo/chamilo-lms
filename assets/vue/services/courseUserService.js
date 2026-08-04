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

  async subscribe(userIds, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-users/actions/subscribe",
      { userIds, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async unsubscribe(userIds, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-users/actions/unsubscribe",
      { userIds, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async setTutor(userId, tutor, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-users/actions/tutor",
      { userIds: [userId], tutor, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async getImport(params = {}) {
    return await baseService.get("/api/course-users/import", cleanParams(params))
  },

  async importCsv(file, replace, csrfToken, params = {}) {
    const formData = new FormData()
    formData.append("file", file)
    formData.append("replace", replace ? "1" : "0")
    formData.append("csrfToken", csrfToken)

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

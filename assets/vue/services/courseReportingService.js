import baseService from "./baseService"

const configurationCache = new Map()

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && String(value) !== ""),
  )
}

export default {
  async getConfiguration(params = {}) {
    const cleanedParams = cleanParams(params)
    const cacheKey = [
      Number(cleanedParams.cid || 0),
      Number(cleanedParams.sid || 0),
      Number(cleanedParams.gid || 0),
    ].join(":")

    if (configurationCache.has(cacheKey)) {
      return await configurationCache.get(cacheKey)
    }

    const request = baseService.get("/api/course-reporting/configuration", cleanedParams)
    configurationCache.set(cacheKey, request)

    try {
      return await request
    } catch (error) {
      configurationCache.delete(cacheKey)
      throw error
    }
  },

  clearConfigurationCache() {
    configurationCache.clear()
  },

  async getOverview(params = {}) {
    return await baseService.get("/api/course-reporting/overview", cleanParams(params))
  },

  async getLearners(params = {}) {
    return await baseService.get("/api/course-reporting/learners", cleanParams(params))
  },

  async getLearnerDetail(params = {}) {
    return await baseService.get("/api/course-reporting/learner-detail", cleanParams(params))
  },

  async getSection(section, params = {}) {
    return await baseService.get(`/api/course-reporting/${section}`, cleanParams(params))
  },

  async downloadLearnersCsv(params = {}) {
    return await baseService.getRaw("/api/course-reporting/learners.csv", {
      params: cleanParams(params),
      responseType: "blob",
    })
  },

  async downloadSection(section, format, params = {}) {
    return await baseService.getRaw(`/api/course-reporting/${section}.${format}`, {
      params: cleanParams(params),
      responseType: "blob",
    })
  },
}

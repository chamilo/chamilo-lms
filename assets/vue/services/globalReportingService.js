import baseService from "./baseService"

let dashboardPromise = null

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && String(value) !== ""),
  )
}

export default {
  async getDashboard(force = false) {
    if (!dashboardPromise || force) {
      dashboardPromise = baseService.get("/api/global-reporting/dashboard")
    }

    try {
      return await dashboardPromise
    } catch (error) {
      dashboardPromise = null
      throw error
    }
  },

  // Cheap redirect-only check, used by the router guard so navigation to /reporting
  // isn't blocked on the full dashboard's expensive metrics queries.
  async getLanding() {
    return await baseService.get("/api/global-reporting/landing")
  },

  clearDashboardCache() {
    dashboardPromise = null
  },

  async getSection(section, params = {}) {
    return await baseService.get("/api/global-reporting/report", cleanParams({ section, ...params }))
  },

  async searchStudentBossLearners(keyword) {
    return await baseService.get("/global-reporting/student-bosses/learners-data", { q: keyword })
  },

  async addLearnerToStudentBoss(bossId, learnerId) {
    return await baseService.post(`/global-reporting/student-bosses/${bossId}/learners`, { learnerId })
  },

  async downloadSection(section, format, params = {}) {
    return await baseService.getRaw(`/api/global-reporting/export/${section}.${format}`, {
      params: cleanParams(params),
      responseType: "blob",
    })
  },
}

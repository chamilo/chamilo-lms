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

  clearDashboardCache() {
    dashboardPromise = null
  },

  async getSection(section, params = {}) {
    return await baseService.get("/api/global-reporting/report", cleanParams({ section, ...params }))
  },

  async downloadSection(section, format, params = {}) {
    return await baseService.getRaw(`/api/global-reporting/export/${section}.${format}`, {
      params: cleanParams(params),
      responseType: "blob",
    })
  },
}

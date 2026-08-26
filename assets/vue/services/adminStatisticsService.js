import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && String(value) !== ""),
  )
}

export default {
  async getReport(params = {}) {
    return await baseService.get("/api/admin/statistics/report", cleanParams(params))
  },

  async downloadSessionByDate(params = {}, format = "xls") {
    return await baseService.getRaw(`/api/admin/statistics/session-by-date.${encodeURIComponent(format)}`, {
      params: cleanParams(params),
      responseType: "blob",
    })
  },

  async runAction(payload = {}) {
    return await baseService.post("/api/admin/statistics/action", payload)
  },

  async downloadReport(report, format, params = {}) {
    return await baseService.getRaw(
      `/api/admin/statistics/export/${encodeURIComponent(report)}.${encodeURIComponent(format)}`,
      {
        params: cleanParams(params),
        responseType: "blob",
      },
    )
  },
}

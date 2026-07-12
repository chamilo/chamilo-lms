import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/course-progress/list", cleanParams(params))
  },

  async getThematicForm(params = {}) {
    return await baseService.get("/api/course-progress/thematic/form", cleanParams(params))
  },

  async createThematic(payload, params = {}) {
    return await baseService.post("/api/course-progress/thematic", payload, {}, { params: cleanParams(params) })
  },

  async updateThematic(iid, payload, params = {}) {
    return await baseService.put(`/api/course-progress/thematic/${iid}`, payload, { params: cleanParams(params) })
  },

  async getThematicPlans(thematicId, params = {}) {
    return await baseService.get(`/api/course-progress/thematic/${thematicId}/plans`, cleanParams(params))
  },

  async saveThematicPlans(thematicId, payload, params = {}) {
    return await baseService.put(`/api/course-progress/thematic/${thematicId}/plans`, payload, {
      params: cleanParams(params),
    })
  },

  async getThematicAdvances(thematicId, params = {}) {
    return await baseService.get(`/api/course-progress/thematic/${thematicId}/advances`, cleanParams(params))
  },

  async getThematicAdvanceForm(thematicId, params = {}) {
    return await baseService.get("/api/course-progress/thematic-advance/form", cleanParams({ ...params, thematicId }))
  },

  async createThematicAdvance(thematicId, payload, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic-advance",
      payload,
      {},
      { params: cleanParams({ ...params, thematicId }) },
    )
  },

  async updateThematicAdvance(thematicId, iid, payload, params = {}) {
    return await baseService.put(`/api/course-progress/thematic-advance/${iid}`, payload, {
      params: cleanParams({ ...params, thematicId }),
    })
  },

  async removeThematicAdvance(thematicId, iid, payload, params = {}) {
    return await baseService.delete(`/api/course-progress/thematic-advance/${iid}`, {
      params: cleanParams({ ...params, thematicId }),
      data: payload,
    })
  },

  async updateCompletion(advanceId, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-progress/completion",
      { advanceId, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async copyThematic(thematicId, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic/actions/copy",
      { thematicId, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async moveThematic(thematicId, direction, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic/actions/move",
      { thematicId, direction, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async removeThematics(thematicIds, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic/actions/bulk-delete",
      { thematicIds, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async exportCsv(params = {}) {
    return await baseService.getRaw("/api/course-progress/export.csv", {
      params: cleanParams(params),
      responseType: "blob",
    })
  },

  async exportPdf(params = {}) {
    return await baseService.getRaw("/api/course-progress/export.pdf", {
      params: cleanParams(params),
      responseType: "blob",
    })
  },

  async exportThematicPdf(thematicId, params = {}) {
    return await baseService.getRaw(`/api/course-progress/thematic/${thematicId}/export.pdf`, {
      params: cleanParams(params),
      responseType: "blob",
    })
  },

  async importCsv(file, replace, csrfToken, params = {}) {
    const formData = new FormData()
    formData.append("file", file)
    formData.append("replace", replace ? "1" : "0")
    formData.append("csrfToken", csrfToken)

    return await baseService.post(
      "/api/course-progress/import.csv",
      formData,
      {},
      {
        params: cleanParams(params),
      },
    )
  },

  async removeThematic(iid, payload, params = {}) {
    return await baseService.delete(`/api/course-progress/thematic/${iid}`, {
      params: cleanParams(params),
      data: payload,
    })
  },
}

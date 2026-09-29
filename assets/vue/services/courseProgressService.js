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
    return await baseService.patch(`/api/course-progress/thematic/${iid}`, payload, { params: cleanParams(params) })
  },

  async getThematicPlans(thematicId, params = {}) {
    return await baseService.get(`/api/course-progress/thematic/${thematicId}/plans`, cleanParams(params))
  },

  async saveThematicPlans(thematicId, payload, params = {}) {
    return await baseService.patch(`/api/course-progress/thematic/${thematicId}/plans`, payload, {
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
    return await baseService.patch(`/api/course-progress/thematic-advance/${iid}`, payload, {
      params: cleanParams({ ...params, thematicId }),
    })
  },

  async removeThematicAdvance(thematicId, iid, params = {}) {
    return await baseService.delete(`/api/course-progress/thematic-advance/${iid}`, {
      params: cleanParams({ ...params, thematicId }),
    })
  },

  async updateCompletion(advanceId, params = {}) {
    return await baseService.post("/api/course-progress/completion", { advanceId }, {}, { params: cleanParams(params) })
  },

  async copyThematic(thematicId, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic/actions/copy",
      { thematicId },
      {},
      { params: cleanParams(params) },
    )
  },

  async moveThematic(thematicId, direction, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic/actions/move",
      { thematicId, direction },
      {},
      { params: cleanParams(params) },
    )
  },

  async removeThematics(thematicIds, params = {}) {
    return await baseService.post(
      "/api/course-progress/thematic/actions/bulk-delete",
      { thematicIds },
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

  async importCsv(file, replace, params = {}) {
    const formData = new FormData()
    formData.append("file", file)
    formData.append("replace", replace ? "1" : "0")

    return await baseService.post(
      "/api/course-progress/import.csv",
      formData,
      {},
      {
        params: cleanParams(params),
      },
    )
  },

  async removeThematic(iid, params = {}) {
    return await baseService.delete(`/api/course-progress/thematic/${iid}`, {
      params: cleanParams(params),
    })
  },
}

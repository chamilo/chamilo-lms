import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

function portfolioFormData(payload = {}) {
  const formData = new FormData()
  const files = Array.isArray(payload.attachments) ? payload.attachments : []
  const extraFiles = payload.extraFiles && typeof payload.extraFiles === "object" ? payload.extraFiles : {}
  const body = { ...payload }

  delete body.attachments
  delete body.extraFiles
  formData.append("payload", JSON.stringify(body))
  files.forEach((file) => formData.append("attachments[]", file))
  Object.entries(extraFiles).forEach(([fieldId, file]) => {
    if (file instanceof File || file instanceof Blob) {
      formData.append(`extraFile_${fieldId}`, file)
    }
  })

  return formData
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/portfolio/list", cleanParams(params))
  },

  async getItem(id, params = {}) {
    return await baseService.get(`/api/portfolio/${id}`, cleanParams(params))
  },

  async getForm(params = {}) {
    return await baseService.get("/api/portfolio/form", cleanParams(params))
  },

  async create(payload, params = {}) {
    return await baseService.post("/api/portfolio", portfolioFormData(payload), {}, { params: cleanParams(params) })
  },

  async update(id, payload, params = {}) {
    return await baseService.post(`/api/portfolio/${id}/edit`, portfolioFormData(payload), {}, { params: cleanParams(params) })
  },

  async itemAction(id, payload, params = {}) {
    return await baseService.post(`/api/portfolio/${id}/action`, payload, {}, { params: cleanParams(params) })
  },

  async createComment(itemId, payload, params = {}) {
    return await baseService.post(
      "/api/portfolio/comments",
      portfolioFormData({ ...payload, itemId }),
      {},
      { params: cleanParams(params) },
    )
  },

  async updateComment(id, payload, params = {}) {
    return await baseService.post(
      "/api/portfolio/comments/edit",
      portfolioFormData({ ...payload, commentId: id }),
      {},
      { params: cleanParams(params) },
    )
  },

  async commentAction(id, payload, params = {}) {
    return await baseService.post(`/api/portfolio/comments/${id}/action`, payload, {}, { params: cleanParams(params) })
  },

  async getManagement(params = {}) {
    return await baseService.get("/api/portfolio/management", cleanParams(params))
  },

  async managementAction(payload, params = {}) {
    return await baseService.post("/api/portfolio/management/action", payload, {}, { params: cleanParams(params) })
  },

  async getDetails(params = {}) {
    return await baseService.get("/api/portfolio/details", cleanParams(params))
  },

  exportPdfUrl(params = {}) {
    const query = new URLSearchParams(cleanParams(params)).toString()
    return `/api/portfolio/export.pdf${query ? `?${query}` : ""}`
  },

  exportZipUrl(params = {}) {
    const query = new URLSearchParams(cleanParams(params)).toString()
    return `/api/portfolio/export.zip${query ? `?${query}` : ""}`
  },
}

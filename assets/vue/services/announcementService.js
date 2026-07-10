import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get(
      "/api/announcement/list",
      { ...cleanParams(params), _ts: Date.now() },
      { headers: { "Cache-Control": "no-cache" } },
    )
  },

  async getItem(id, params = {}) {
    return await baseService.get(`/api/announcement/${id}`, cleanParams(params))
  },

  async getForm(params = {}) {
    return await baseService.get("/api/announcement/form", cleanParams(params))
  },

  async preview(payload, params = {}) {
    return await baseService.post("/api/announcement/preview", payload, {}, { params: cleanParams(params) })
  },

  async create(payload, params = {}) {
    return await baseService.post("/api/announcement", payload, {}, { params: cleanParams(params) })
  },

  async update(id, payload, params = {}) {
    return await baseService.put(`/api/announcement/${id}`, payload, { params: cleanParams(params) })
  },

  async changeVisibility(id, visibility, csrfToken, params = {}) {
    return await baseService.post(
      `/api/announcement/${id}/visibility`,
      { visibility, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async move(id, direction, csrfToken, params = {}) {
    return await baseService.post(
      `/api/announcement/${id}/move`,
      { direction, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteOne(id, csrfToken, params = {}) {
    return await baseService.post(
      `/api/announcement/${id}/delete`,
      { csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteSelected(ids, csrfToken, params = {}) {
    return await baseService.post(
      "/api/announcement/delete-selected",
      { ids, csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteAll(csrfToken, params = {}) {
    return await baseService.post(
      "/api/announcement/delete-all",
      { csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async uploadAttachments(id, files, comment, csrfToken, params = {}) {
    const formData = new FormData()
    files.forEach((file) => formData.append("files[]", file))
    formData.append("comment", comment || "")
    formData.append("csrfToken", csrfToken)

    return await baseService.post(`/api/announcement/${id}/attachments`, formData, {}, { params: cleanParams(params) })
  },

  async deleteAttachment(announcementId, attachmentId, csrfToken, params = {}) {
    return await baseService.delete(`/api/announcement/${announcementId}/attachment/${attachmentId}`, {
      params: cleanParams(params),
      headers: { "X-CSRF-TOKEN": csrfToken },
    })
  },
}

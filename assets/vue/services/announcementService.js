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
    return await baseService.patch(`/api/announcement/${id}`, payload, { params: cleanParams(params) })
  },

  async changeVisibility(id, visibility, params = {}) {
    return await baseService.post(
      `/api/announcement/${id}/visibility`,
      { visibility },
      {},
      {
        params: cleanParams(params),
      },
    )
  },

  async move(id, direction, params = {}) {
    return await baseService.post(
      `/api/announcement/${id}/move`,
      { direction },
      {},
      {
        params: cleanParams(params),
      },
    )
  },

  async deleteOne(id, params = {}) {
    return await baseService.post(`/api/announcement/${id}/delete`, {}, {}, { params: cleanParams(params) })
  },

  async deleteSelected(ids, params = {}) {
    return await baseService.post(
      "/api/announcement/delete-selected",
      { ids },
      {},
      {
        params: cleanParams(params),
      },
    )
  },

  async deleteAll(params = {}) {
    return await baseService.post("/api/announcement/delete-all", {}, {}, { params: cleanParams(params) })
  },

  async sendEmail(id, payload, params = {}) {
    return await baseService.post(
      `/api/announcement/${id}/send-email`,
      payload,
      {},
      {
        params: cleanParams(params),
      },
    )
  },

  async uploadAttachments(id, files, comment, params = {}) {
    const formData = new FormData()
    files.forEach((file) => formData.append("files[]", file))
    formData.append("comment", comment || "")

    return await baseService.post(`/api/announcement/${id}/attachments`, formData, {}, { params: cleanParams(params) })
  },

  async deleteAttachment(announcementId, attachmentId, params = {}) {
    return await baseService.delete(`/api/announcement/${announcementId}/attachment/${attachmentId}`, {
      params: cleanParams(params),
    })
  },
}

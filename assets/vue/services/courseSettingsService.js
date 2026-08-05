import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

function formData(file, csrfToken, extra = {}) {
  const data = new FormData()
  data.append("file", file)
  data.append("csrfToken", csrfToken)

  Object.entries(extra).forEach(([key, value]) => {
    if (value !== undefined && value !== null) {
      data.append(key, String(value))
    }
  })

  return data
}

export default {
  async getConfiguration(params = {}) {
    return await baseService.get("/api/course-settings", cleanParams(params))
  },

  async save(values, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-settings",
      { values, submittedCsrfToken: csrfToken },
      {},
      { params: cleanParams(params) },
    )
  },

  async uploadPicture(file, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-settings/picture",
      formData(file, csrfToken),
      {},
      { params: cleanParams(params) },
    )
  },

  async deletePicture(csrfToken, params = {}) {
    return await baseService.delete("/api/course-settings/picture", {
      params: cleanParams(params),
      headers: { "X-CSRF-TOKEN": csrfToken },
    })
  },

  async generatePicture(prompt, token, courseId) {
    return await baseService.post("/ai/generate_course_picture", {
      cid: courseId,
      prompt,
      _token: token,
    })
  },

  async uploadWatermark(file, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-settings/watermark",
      formData(file, csrfToken),
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteWatermark(csrfToken, params = {}) {
    return await baseService.delete("/api/course-settings/watermark", {
      params: cleanParams(params),
      headers: { "X-CSRF-TOKEN": csrfToken },
    })
  },

  async uploadCourseLegalFile(file, csrfToken, params = {}) {
    return await baseService.post(
      "/api/course-settings/course-legal-file",
      formData(file, csrfToken),
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteCourseLegalFile(csrfToken, params = {}) {
    return await baseService.delete("/api/course-settings/course-legal-file", {
      params: cleanParams(params),
      headers: { "X-CSRF-TOKEN": csrfToken },
    })
  },

  courseLegalFileUrl(params = {}) {
    return `/api/course-settings/course-legal-file?${new URLSearchParams(cleanParams(params)).toString()}`
  },

  async uploadCertificateMedia(field, file, csrfToken, params = {}) {
    return await baseService.post(
      `/api/course-settings/custom-certificate-media/${encodeURIComponent(field)}`,
      formData(file, csrfToken),
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteCertificateMedia(field, csrfToken, params = {}) {
    return await baseService.delete(`/api/course-settings/custom-certificate-media/${encodeURIComponent(field)}`, {
      params: cleanParams(params),
      headers: { "X-CSRF-TOKEN": csrfToken },
    })
  },

  certificateMediaUrl(field, params = {}) {
    return `/api/course-settings/custom-certificate-media/${encodeURIComponent(field)}?${new URLSearchParams(cleanParams(params)).toString()}`
  },
}

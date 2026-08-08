import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

function formData(file, extra = {}) {
  const data = new FormData()
  data.append("file", file)

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

  async save(values, params = {}) {
    return await baseService.post("/api/course-settings", { values }, {}, { params: cleanParams(params) })
  },

  async uploadPicture(file, params = {}) {
    return await baseService.post("/api/course-settings/picture", formData(file), {}, { params: cleanParams(params) })
  },

  async deletePicture(params = {}) {
    return await baseService.delete("/api/course-settings/picture", {
      params: cleanParams(params),
    })
  },

  async generatePicture(prompt, courseId) {
    return await baseService.post("/ai/generate_course_picture", {
      cid: courseId,
      prompt,
    })
  },

  async uploadWatermark(file, params = {}) {
    return await baseService.post("/api/course-settings/watermark", formData(file), {}, { params: cleanParams(params) })
  },

  async deleteWatermark(params = {}) {
    return await baseService.delete("/api/course-settings/watermark", {
      params: cleanParams(params),
    })
  },

  async uploadCourseLegalFile(file, params = {}) {
    return await baseService.post(
      "/api/course-settings/course-legal-file",
      formData(file),
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteCourseLegalFile(params = {}) {
    return await baseService.delete("/api/course-settings/course-legal-file", {
      params: cleanParams(params),
    })
  },

  courseLegalFileUrl(params = {}) {
    return `/api/course-settings/course-legal-file?${new URLSearchParams(cleanParams(params)).toString()}`
  },

  async uploadCertificateMedia(field, file, params = {}) {
    return await baseService.post(
      `/api/course-settings/custom-certificate-media/${encodeURIComponent(field)}`,
      formData(file),
      {},
      { params: cleanParams(params) },
    )
  },

  async deleteCertificateMedia(field, params = {}) {
    return await baseService.delete(`/api/course-settings/custom-certificate-media/${encodeURIComponent(field)}`, {
      params: cleanParams(params),
    })
  },

  certificateMediaUrl(field, params = {}) {
    return `/api/course-settings/custom-certificate-media/${encodeURIComponent(field)}?${new URLSearchParams(cleanParams(params)).toString()}`
  },
}

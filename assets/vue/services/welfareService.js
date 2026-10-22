import baseService from "./baseService"

const options = { skipCourseContext: true }

export default {
  availability() {
    return baseService.get("/api/welfare/availability", {}, options)
  },
  list() {
    return baseService.get("/api/welfare", {}, options)
  },
  targets(q) {
    return baseService.get("/api/welfare/targets", { q }, options)
  },
  investigations() {
    return baseService.get("/api/welfare/investigations", {}, options)
  },
  targetReports(targetId) {
    return baseService.get(`/api/welfare/investigations/${Number(targetId)}`, {}, options)
  },
  detail(reference) {
    return baseService.get(`/api/welfare/${reference}`, {}, options)
  },
  create(formData) {
    return baseService.post("/api/welfare", formData, {}, options)
  },
  reply(reference, formData) {
    return baseService.post(`/api/welfare/${reference}/messages`, formData, {}, options)
  },
  changeContact(reference, shareContact) {
    return baseService.post(`/api/welfare/${reference}/contact`, { shareContact }, {}, options)
  },
  downloadEvidence(reference, id) {
    // Follow the same authenticated Axios request path as other API calls.
    // A bare browser link may omit the JWT used by the application.
    return baseService.getRaw(`/api/welfare/${reference}/attachments/${Number(id)}`, {
      skipCourseContext: true,
      responseType: "blob",
      headers: { Accept: "application/octet-stream" },
    })
  },
}

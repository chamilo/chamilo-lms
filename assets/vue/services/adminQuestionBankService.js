import baseService from "./baseService"

const ENDPOINT = "/api/admin/questions"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

export default {
  async getData(params = {}, signal = undefined) {
    return await baseService.get(ENDPOINT, cleanParams(params), {
      skipCourseContext: true,
      signal,
    })
  },

  async deleteQuestion(questionId) {
    return await baseService.post(
      `${ENDPOINT}/action`,
      {
        action: "delete",
        questionId,
      },
      {},
      { skipCourseContext: true },
    )
  },

  async exportPdf(params = {}) {
    return await baseService.getRaw(`${ENDPOINT}/export.pdf`, {
      params: cleanParams(params),
      responseType: "blob",
      skipCourseContext: true,
    })
  },
}

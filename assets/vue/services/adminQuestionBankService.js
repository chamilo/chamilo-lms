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

  async deleteQuestion(questionId, submittedCsrfToken) {
    return await baseService.post(
      `${ENDPOINT}/action`,
      {
        action: "delete",
        questionId,
        submittedCsrfToken,
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

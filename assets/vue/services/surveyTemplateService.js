import baseService from "./baseService"

function cleanParams(params = {}) {
  const query = {}

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && String(value) !== "") {
      query[key] = value
    }
  }

  return query
}

function buildQueryString(params = {}) {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(cleanParams(params))) {
    query.set(key, String(value))
  }

  const queryString = query.toString()

  return queryString ? `?${queryString}` : ""
}

function requestConfig(config = {}) {
  const { headers = {}, ...restConfig } = config

  return {
    skipCourseContext: true,
    ...restConfig,
    headers: { ...headers },
  }
}

export default {
  async getCatalog(params = {}) {
    return await baseService.get("/api/survey/templates", cleanParams(params), requestConfig())
  },

  async getTargetCatalog() {
    return await baseService.get("/api/survey/templates/targets", {}, requestConfig())
  },

  async getConfiguration(templateId = null, params = {}) {
    const endpoint = templateId ? `/api/survey/templates/${templateId}` : "/api/survey/templates/configuration"

    return await baseService.get(endpoint, cleanParams(params), requestConfig())
  },

  async saveConfiguration(payload, templateId = null) {
    if (templateId) {
      return await baseService.patch(`/api/survey/templates/${templateId}`, payload, requestConfig())
    }

    return await baseService.post("/api/survey/templates", payload, {}, requestConfig())
  },

  async deleteTemplate(templateId) {
    return await baseService.delete(`/api/survey/templates/${templateId}`, requestConfig())
  },

  async instantiate(templateId, payload = {}, params = {}) {
    return await baseService.post(
      `/api/survey/templates/${templateId}/instantiate${buildQueryString(params)}`,
      payload,
      {},
      requestConfig(),
    )
  },

  async getQuestions(templateId) {
    return await baseService.get(`/api/survey/templates/${templateId}/questions`, {}, requestConfig())
  },

  async saveQuestion(templateId, payload, questionId = null) {
    if (questionId) {
      return await baseService.patch(
        `/api/survey/templates/${templateId}/questions/${questionId}`,
        payload,
        requestConfig(),
      )
    }

    return await baseService.post(`/api/survey/templates/${templateId}/questions`, payload, {}, requestConfig())
  },

  async deleteQuestion(templateId, questionId) {
    return await baseService.delete(`/api/survey/templates/${templateId}/questions/${questionId}`, requestConfig())
  },

  async moveQuestion(templateId, questionId, direction) {
    return await baseService.post(
      `/api/survey/templates/${templateId}/questions/${questionId}/move`,
      { direction },
      {},
      requestConfig(),
    )
  },

  async copyQuestion(templateId, questionId) {
    return await baseService.post(
      `/api/survey/templates/${templateId}/questions/${questionId}/copy`,
      {},
      {},
      requestConfig(),
    )
  },
}

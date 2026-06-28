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

function getPayloadCsrfToken(payload = {}) {
  return payload?.csrfToken ? String(payload.csrfToken) : ""
}

function surveyRequestConfig(config = {}, csrfToken = "") {
  const { headers = {}, ...restConfig } = config
  const mergedHeaders = { ...headers }

  if (csrfToken) {
    mergedHeaders["X-CSRF-Token"] = csrfToken
  }

  return {
    skipCourseContext: true,
    ...restConfig,
    headers: mergedHeaders,
  }
}

export default {
  async getSurveyList(params = {}) {
    return await baseService.get("/api/survey/list", cleanParams(params), surveyRequestConfig())
  },

  async getPendingSurveys(params = {}) {
    return await baseService.get("/api/survey/pending", cleanParams(params), surveyRequestConfig())
  },

  async getSurveyConfiguration(params = {}, surveyId = null) {
    const endpoint = surveyId ? `/api/survey/configuration/${surveyId}` : "/api/survey/configuration"

    return await baseService.get(endpoint, cleanParams(params), surveyRequestConfig())
  },

  async saveSurveyConfiguration(payload, params = {}, surveyId = null) {
    const queryString = buildQueryString(params)

    if (surveyId) {
      return await baseService.put(
        `/api/survey/configuration/${surveyId}${queryString}`,
        payload,
        surveyRequestConfig({}, getPayloadCsrfToken(payload)),
      )
    }

    return await baseService.post(
      `/api/survey/configuration${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async getSurveyMeeting(params = {}, surveyId = null) {
    const endpoint = surveyId ? `/api/survey/meeting/${surveyId}` : "/api/survey/meeting"

    return await baseService.get(endpoint, cleanParams(params), surveyRequestConfig())
  },

  async saveSurveyMeeting(payload, params = {}, surveyId = null) {
    const queryString = buildQueryString(params)

    if (surveyId) {
      return await baseService.put(
        `/api/survey/meeting/${surveyId}${queryString}`,
        payload,
        surveyRequestConfig({}, getPayloadCsrfToken(payload)),
      )
    }

    return await baseService.post(
      `/api/survey/meeting${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async submitSurveyMeetingAnswer(payload, params = {}, surveyId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/survey/meeting/${surveyId}/answer${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async getSurveyQuestions(params = {}, surveyId) {
    return await baseService.get(`/api/survey/questions/${surveyId}`, cleanParams(params), surveyRequestConfig())
  },

  async saveSurveyQuestion(payload, params = {}, surveyId, questionId = null) {
    const queryString = buildQueryString(params)

    if (questionId) {
      return await baseService.put(
        `/api/survey/questions/${surveyId}/${questionId}${queryString}`,
        payload,
        surveyRequestConfig({}, getPayloadCsrfToken(payload)),
      )
    }

    return await baseService.post(
      `/api/survey/questions/${surveyId}${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async deleteSurveyQuestion(params = {}, surveyId, questionId, csrfToken) {
    const queryString = buildQueryString(params)

    return await baseService.delete(`/api/survey/questions/${surveyId}/${questionId}${queryString}`, {
      data: { csrfToken },
      ...surveyRequestConfig({}, csrfToken),
    })
  },

  async moveSurveyQuestion(params = {}, surveyId, questionId, direction, csrfToken) {
    const queryString = buildQueryString(params)

    return await baseService.post(`/api/survey/questions/${surveyId}/${questionId}/move${queryString}`, {
      direction,
      csrfToken,
    }, {}, surveyRequestConfig({}, csrfToken))
  },

  async copySurveyQuestion(params = {}, surveyId, questionId, csrfToken) {
    const queryString = buildQueryString(params)

    return await baseService.post(`/api/survey/questions/${surveyId}/${questionId}/copy${queryString}`, {
      csrfToken,
    }, {}, surveyRequestConfig({}, csrfToken))
  },

  async getSurveyAnswer(params = {}, surveyId) {
    return await baseService.get(`/api/survey/answer/${surveyId}`, cleanParams(params), surveyRequestConfig())
  },

  async submitSurveyAnswer(payload, params = {}, surveyId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/survey/answer/${surveyId}${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async getSurveyInvitations(params = {}, surveyId) {
    return await baseService.get(`/api/survey/invitations/${surveyId}`, cleanParams(params), surveyRequestConfig())
  },

  async publishSurveyInvitations(payload, params = {}, surveyId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/survey/invitations/${surveyId}/publish${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async runSurveyAction(params = {}, surveyId, action, csrfToken) {
    const queryString = buildQueryString(params)

    return await baseService.post(`/api/survey/actions/${surveyId}/${action}${queryString}`, {
      csrfToken,
    }, {}, surveyRequestConfig({}, csrfToken))
  },

  async runSurveyBulkDelete(params = {}, surveyIds = [], csrfToken) {
    const queryString = buildQueryString(params)

    return await baseService.post(`/api/survey/actions/bulk-delete${queryString}`, {
      surveyIds,
      csrfToken,
    }, {}, surveyRequestConfig({}, csrfToken))
  },

  async getSurveyCopy(params = {}, surveyId) {
    return await baseService.get(`/api/survey/actions/${surveyId}/copy`, cleanParams(params), surveyRequestConfig())
  },

  async copySurveyToTarget(payload, params = {}, surveyId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/survey/actions/${surveyId}/copy${queryString}`,
      payload,
      {},
      surveyRequestConfig({}, getPayloadCsrfToken(payload)),
    )
  },

  async getSurveyReporting(params = {}, surveyId) {
    return await baseService.get(`/api/survey/reporting/${surveyId}`, cleanParams(params), surveyRequestConfig())
  },

  buildSurveyReportingCsvUrl(params = {}, surveyId) {
    return `/api/survey/reporting/${surveyId}/export.csv${buildQueryString(params)}`
  },

  buildSurveyReportingCompactCsvUrl(params = {}, surveyId) {
    return `/api/survey/reporting/${surveyId}/export.csv${buildQueryString({ ...params, compact: 1 })}`
  },

  buildSurveyReportingXlsxUrl(params = {}, surveyId) {
    return `/api/survey/reporting/${surveyId}/export.xlsx${buildQueryString(params)}`
  },

  buildSurveyReportingByClassXlsxUrl(params = {}, surveyId) {
    return `/api/survey/reporting/${surveyId}/export-by-class.xlsx${buildQueryString(params)}`
  },

  buildSurveyReportingZipUrl(params = {}, surveyId) {
    return `/api/survey/reporting/${surveyId}/export.zip${buildQueryString(params)}`
  },
}

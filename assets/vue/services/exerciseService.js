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

function exerciseRequestConfig(config = {}) {
  return {
    skipCourseContext: true,
    ...config,
  }
}

export default {
  async getExerciseList(params = {}) {
    return await baseService.get("/api/exercise/list", cleanParams(params), exerciseRequestConfig())
  },

  async saveExerciseListAction(payload = {}, params = {}) {
    const queryString = buildQueryString(params)

    return await baseService.post(`/api/exercise/list/action${queryString}`, payload, {}, exerciseRequestConfig())
  },

  async getExerciseOverview(params = {}, exerciseId) {
    return await baseService.get(`/api/exercise/overview/${exerciseId}`, cleanParams(params), exerciseRequestConfig())
  },

  async getExerciseCategories(categoryType, params = {}) {
    return await baseService.get(`/api/exercise/categories/${categoryType}`, cleanParams(params), exerciseRequestConfig())
  },

  async saveExerciseCategoryAction(categoryType, payload = {}, params = {}) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/categories/${categoryType}/action${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async getExerciseConfiguration(params = {}, exerciseId = null) {
    const endpoint = exerciseId ? `/api/exercise/configuration/${exerciseId}` : "/api/exercise/configuration"

    return await baseService.get(endpoint, cleanParams(params), exerciseRequestConfig())
  },


  async getExerciseQuestions(params = {}, exerciseId) {
    return await baseService.get(`/api/exercise/questions/${exerciseId}`, cleanParams(params), exerciseRequestConfig())
  },

  async getExerciseGlobalQuestionTypes(params = {}) {
    return await baseService.get('/api/exercise/questions/global', cleanParams(params), exerciseRequestConfig())
  },

  async getExerciseRuntime(params = {}, exerciseId) {
    return await baseService.get(`/api/exercise/runtime/${exerciseId}`, cleanParams(params), exerciseRequestConfig())
  },

  async startExerciseAttempt(payload = {}, params = {}, exerciseId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async saveExerciseRuntimeAnswer(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/answer${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },


  async uploadExerciseRuntimeAnswer(formData, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.postForm(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/upload-answer${queryString}`,
      formData,
      exerciseRequestConfig(),
    )
  },

  async finishExerciseRuntimeAttempt(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/finish${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async getExerciseRuntimeResult(params = {}, exerciseId, attemptId) {
    return await baseService.get(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/result`,
      cleanParams(params),
      exerciseRequestConfig(),
    )
  },

  async getExerciseRuntimeReport(params = {}, exerciseId) {
    return await baseService.get(
      `/api/exercise/runtime/${exerciseId}/attempts`,
      cleanParams(params),
      exerciseRequestConfig(),
    )
  },

  async runExerciseRuntimeReportBulkAction(payload = {}, params = {}, exerciseId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempts/action${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async getExerciseQuestionStats(params = {}, exerciseId) {
    return await baseService.get(
      `/api/exercise/runtime/${exerciseId}/question-stats`,
      cleanParams(params),
      exerciseRequestConfig(),
    )
  },

  async getExerciseReportByQuestion(params = {}, exerciseId) {
    return await baseService.get(
      `/api/exercise/runtime/${exerciseId}/report-by-question`,
      cleanParams(params),
      exerciseRequestConfig(),
    )
  },

  async getExerciseLiveResults(params = {}, exerciseId) {
    return await baseService.get(
      `/api/exercise/runtime/${exerciseId}/live-results`,
      cleanParams(params),
      exerciseRequestConfig(),
    )
  },

  async deleteExerciseRuntimeAttempt(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/delete${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async closeExerciseRuntimeAttempt(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/close${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async recalculateExerciseRuntimeAttempt(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/recalculate${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async emailExerciseRuntimeAttempt(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/email${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async emailExerciseRuntimeReportAttempts(payload = {}, params = {}, exerciseId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempts/email${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  buildExerciseRuntimeAttemptPdfUrl(params = {}, exerciseId, attemptId) {
    return `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/pdf${buildQueryString(params)}`
  },

  async saveExerciseRuntimeCorrection(payload = {}, params = {}, exerciseId, attemptId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/runtime/${exerciseId}/attempt/${attemptId}/correction${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async getExerciseGlobalQuestionEditor(params = {}, questionType = null) {
    const queryParams = { ...params }

    if (questionType !== null && questionType !== undefined) {
      queryParams.type = questionType
    }

    const endpoint = questionType !== null && questionType !== undefined
      ? '/api/exercise/questions/global/editor'
      : `/api/exercise/questions/global/editor/${params.questionId}`

    delete queryParams.questionId

    return await baseService.get(endpoint, cleanParams(queryParams), exerciseRequestConfig())
  },

  async saveExerciseGlobalQuestion(payload, params = {}, questionId = null) {
    const queryString = buildQueryString(params)
    const endpoint = questionId
      ? `/api/exercise/questions/global/editor/${questionId}${queryString}`
      : `/api/exercise/questions/global/editor${queryString}`

    return await baseService.post(endpoint, payload, {}, exerciseRequestConfig())
  },

  async getExerciseQuestionEditor(params = {}, exerciseId, questionId = null, questionType = null) {
    const queryParams = { ...params }

    if (questionType !== null && questionType !== undefined) {
      queryParams.type = questionType
    }

    const endpoint = questionId
      ? `/api/exercise/questions/${exerciseId}/editor/${questionId}`
      : `/api/exercise/questions/${exerciseId}/editor`

    return await baseService.get(endpoint, cleanParams(queryParams), exerciseRequestConfig())
  },

  async saveExerciseQuestion(payload, params = {}, exerciseId, questionId = null) {
    const queryString = buildQueryString(params)
    const endpoint = questionId
      ? `/api/exercise/questions/${exerciseId}/editor/${questionId}${queryString}`
      : `/api/exercise/questions/${exerciseId}/editor${queryString}`

    return await baseService.post(endpoint, payload, {}, exerciseRequestConfig())
  },

  async saveExerciseQuestionAction(payload, params = {}, exerciseId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/questions/${exerciseId}/action${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async attachExerciseToLearningPath(payload = {}, params = {}, exerciseId) {
    const queryString = buildQueryString(params)

    return await baseService.post(
      `/api/exercise/questions/${exerciseId}/learning-path-item${queryString}`,
      payload,
      {},
      exerciseRequestConfig(),
    )
  },

  async getExerciseQuestionBank(params = {}, exerciseId = null) {
    const endpoint = exerciseId ? `/api/exercise/questions/${exerciseId}/bank` : '/api/exercise/questions/bank'

    return await baseService.get(endpoint, cleanParams(params), exerciseRequestConfig())
  },

  async saveExerciseQuestionBankAction(payload, params = {}, exerciseId = null) {
    const queryString = buildQueryString(params)
    const endpoint = exerciseId
      ? `/api/exercise/questions/${exerciseId}/bank/action${queryString}`
      : `/api/exercise/questions/bank/action${queryString}`

    return await baseService.post(endpoint, payload, {}, exerciseRequestConfig())
  },

  async getExerciseAiAikenGenerator(params = {}) {
    return await baseService.get("/api/exercise/ai-aiken-generator", cleanParams(params), exerciseRequestConfig())
  },

  async generateExerciseAikenFromTopic(payload = {}) {
    return await baseService.post("/ai/generate_aiken", payload, {}, exerciseRequestConfig())
  },

  async generateExerciseAikenFromDocument(payload = {}) {
    return await baseService.post("/ai/generate_aiken_from_document", payload, {}, exerciseRequestConfig())
  },

  async getExerciseQuestionImport(importType, params = {}) {
    return await baseService.get(`/api/exercise/import/${importType}`, cleanParams(params), exerciseRequestConfig())
  },

  async importExerciseQuestions(importType, formData, params = {}) {
    const queryString = buildQueryString(params)

    return await baseService.postForm(`/api/exercise/import/${importType}${queryString}`, formData, exerciseRequestConfig())
  },

  async saveExerciseConfiguration(payload, params = {}, exerciseId = null) {
    const queryString = buildQueryString(params)

    if (exerciseId) {
      return await baseService.put(`/api/exercise/configuration/${exerciseId}${queryString}`, payload, exerciseRequestConfig())
    }

    return await baseService.post(`/api/exercise/configuration${queryString}`, payload, {}, exerciseRequestConfig())
  },
}

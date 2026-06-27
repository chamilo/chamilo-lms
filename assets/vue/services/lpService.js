import baseService from "./baseService"

const cleanParams = (params = {}) =>
  Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && String(value) !== ""),
  )

/** Lists learning paths filtered by course/session/title. */
const getLearningPaths = async (params) => {
  const { items } = await baseService.getCollection(`/api/learning_paths`, params)

  return items
}

/** Fetches a learning path by ID (iid). */
const getLearningPath = async (lpId) => {
  return await baseService.get(`/api/learning_paths/${lpId}/`)
}

/** Builds the Vue learner runtime URL. */
const buildRuntimeUrl = (
  lpId,
  { cid, sid = 0, gid = 0, node, gradebook = 0, origin = "learnpath", isStudentView = "true", itemId = 0 } = {},
) => {
  const qs = new URLSearchParams({
    sid: Number(sid),
    gid: Number(gid),
    gradebook: Number(gradebook),
    origin,
    isStudentView,
  })

  if (Number(cid) > 0) {
    qs.set("cid", Number(cid))
  }

  if (Number(itemId) > 0) {
    qs.set("item_id", Number(itemId))
  }

  return `/resources/lp/${Number(node)}/${Number(lpId)}/runtime?${qs.toString()}`
}

/** Loads the validated learner runtime state. */
const getRuntime = async (lpId, params = {}) =>
  await baseService.get(`/api/learning_paths/${lpId}/runtime`, cleanParams(params))

/** Records the current runtime item after backend access checks. */
const openRuntimeItem = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/runtime/item`, payload, {}, { params: cleanParams(params) })

/** Synchronizes progress after the active tool updates its LP item view. */
const syncRuntime = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/runtime/sync`, payload, {}, { params: cleanParams(params) })

/** Persists the active item timer while the browser page is being unloaded. */
const syncRuntimeBeacon = (lpId, params, payload) => {
  if (!navigator.sendBeacon) {
    return false
  }

  const query = new URLSearchParams(cleanParams(params))
  const body = new Blob([JSON.stringify(payload)], { type: "application/json" })

  return navigator.sendBeacon(`/api/learning_paths/${lpId}/runtime/sync?${query.toString()}`, body)
}

/** Persists the active SCORM data model values. */
const commitScormRuntime = async (lpId, itemId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/runtime/scorm/commit`, payload, {}, {
    params: cleanParams(params),
  })

/** Persists SCORM values while the browser page is being unloaded. */
const commitScormRuntimeBeacon = (lpId, itemId, params, payload) => {
  if (!navigator.sendBeacon) {
    return false
  }

  const query = new URLSearchParams(cleanParams(params))
  const body = new Blob([JSON.stringify(payload)], { type: "application/json" })

  return navigator.sendBeacon(
    `/api/learning_paths/${lpId}/runtime/scorm/commit?${query.toString()}`,
    body,
  )
}

/** Creates a new whole-learning-path attempt after backend validation. */
const restartRuntime = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/runtime/restart`, payload, {}, { params: cleanParams(params) })

/** Imports a SCORM ZIP package into the current validated context. */
const importScormPackage = async (params, formData) =>
  await baseService.post("/api/learning_paths/scorm/import", formData, {}, { params: cleanParams(params) })

/** Replaces the files of an existing SCORM package after backend compatibility validation. */
const updateScormPackage = async (lpId, params, formData) =>
  await baseService.post(`/api/learning_paths/${lpId}/scorm/update`, formData, {}, {
    params: cleanParams(params),
  })

/** Builds the download URL for the original package of a SCORM learning path. */
const buildScormPackageDownloadUrl = (lpId, params = {}) => {
  const query = new URLSearchParams(cleanParams(params))

  return `/api/learning_paths/${lpId}/scorm/package?${query.toString()}`
}

/** Loads the validated AI generator configuration for the current course context. */
const getAiGeneratorConfiguration = async (params = {}) =>
  await baseService.get("/api/learning_paths/ai-generator", cleanParams(params))

/** Requests generated learning-path content from the configured AI provider. */
const generateAiLearningPath = async (params, payload) =>
  await baseService.post("/ai/generate_learnpath", payload, {}, { params: cleanParams(params) })

/** Persists validated generated content as a learning path in the current context. */
const saveAiLearningPath = async (params, payload) =>
  await baseService.post("/api/learning_paths/ai-generator", payload, {}, { params: cleanParams(params) })

/** Fetches the CSRF token used by modern LP write actions. */
const getActionToken = async (params = {}) => {
  return await baseService.get("/api/learning_paths/action-token", cleanParams(params))
}

/** Toggles LP visibility in the current course/session/group context. */
const toggleVisibility = async (lpId, params, payload) => {
  return await baseService.put(`/api/learning_paths/${lpId}/toggle-visibility`, payload, {
    params: cleanParams(params),
  })
}

/** Toggles LP category visibility in the current course/session/group context. */
const toggleCategoryVisibility = async (categoryId, params, payload) => {
  return await baseService.put(`/api/learning_path_categories/${categoryId}/toggle-visibility`, payload, {
    params: cleanParams(params),
  })
}

/** Executes a validated management action for one learning path. */
const manageLearningPath = async (lpId, params, payload) => {
  return await baseService.post(`/api/learning_paths/${lpId}/manage`, payload, {}, {
    params: cleanParams(params),
  })
}

/** Persists LP display order inside the current validated context. */
const reorder = async (params, payload) => {
  await baseService.post("/api/learning_paths/reorder", payload, {}, {
    params: cleanParams(params),
  })
}

/** Atomically persists category order, LP order and LP category assignment. */
const saveLayout = async (params, payload) => {
  await baseService.post("/api/learning_paths/layout", payload, {}, {
    params: cleanParams(params),
  })
}

/**
 * Lists LP categories for a course (empty included).
 *
 * @param {Object} searchParams
 * @returns {Promise<Object[]>}
 */
const getLpCategories = async (searchParams) => {
  const { items } = await baseService.getCollection("/api/learning_path_categories", searchParams)

  return items
}

const getConfiguration = async (lpId, params) => {
  const endpoint = lpId
    ? `/api/learning_paths/${lpId}/configuration`
    : "/api/learning_paths/configuration"

  return await baseService.get(endpoint, cleanParams(params))
}

const saveConfiguration = async (lpId, params, payload, imageFile = null, extraFiles = {}) => {
  const formData = new FormData()
  formData.append("payload", JSON.stringify(payload))

  if (imageFile instanceof File) {
    formData.append("image", imageFile)
  }

  Object.entries(extraFiles).forEach(([fieldId, file]) => {
    if (file instanceof File) {
      formData.append(`extraFile_${fieldId}`, file)
    }
  })

  const endpoint = lpId
    ? `/api/learning_paths/${lpId}/configuration`
    : "/api/learning_paths/configuration"

  return await baseService.post(endpoint, formData, {}, {
    params: cleanParams(params),
  })
}

const getBuilder = async (lpId, params) =>
  await baseService.get(`/api/learning_paths/${lpId}/builder`, cleanParams(params))

const createBuilderSection = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/builder/sections`, payload, {}, { params: cleanParams(params) })

const updateBuilderItem = async (lpId, itemId, params, payload, extraFiles = {}) => {
  const formData = new FormData()
  formData.append("payload", JSON.stringify({ ...payload, lpId }))

  Object.entries(extraFiles).forEach(([fieldId, file]) => {
    if (file instanceof File) {
      formData.append(`extraFile_${fieldId}`, file)
    }
  })

  return await baseService.post(`/api/learning_path_builder_items/${itemId}/edit`, formData, {}, {
    params: cleanParams(params),
  })
}

const deleteBuilderItem = async (lpId, itemId, params, payload) =>
  await baseService.post(`/api/learning_path_builder_items/${itemId}/delete`, { ...payload, lpId }, {}, {
    params: cleanParams(params),
  })

const reorderBuilderItems = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/builder/reorder`, payload, {}, { params: cleanParams(params) })

const addBuilderResource = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/builder/resources`, payload, {}, { params: cleanParams(params) })

const updateBuilderPrerequisites = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/builder/prerequisites`, payload, {}, {
    params: cleanParams(params),
  })

const updateBuilderItemPrerequisite = async (lpId, itemId, params, payload) =>
  await baseService.put(`/api/learning_path_builder_items/${itemId}/prerequisite`, { ...payload, lpId }, {
    params: cleanParams(params),
  })

const updateBuilderItemAudio = async (lpId, itemId, params, payload) =>
  await baseService.put(`/api/learning_path_builder_items/${itemId}/audio`, { ...payload, lpId }, {
    params: cleanParams(params),
  })

const updateBuilderBulkAuthorPrice = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/builder/author-price`, payload, {}, {
    params: cleanParams(params),
  })

const createBuilderDocument = async (params, formData) =>
  await baseService.post("/api/documents", formData, {}, { params: cleanParams(params) })

const updateBuilderDocument = async (documentId, params, payload) =>
  await baseService.put(`/api/documents/${documentId}`, payload, { params: cleanParams(params) })

const saveBuilderFinalItem = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/builder/final-item`, payload, {}, {
    params: cleanParams(params),
  })

const createCategory = async (params, payload) =>
  await baseService.post("/api/learning_path_categories/manage", payload, {}, { params: cleanParams(params) })

const updateCategory = async (categoryId, params, payload) =>
  await baseService.put(`/api/learning_path_categories/${categoryId}/manage`, payload, {
    params: cleanParams(params),
  })

const deleteCategory = async (categoryId, params, csrfToken) =>
  await baseService.post(
    `/api/learning_path_categories/${categoryId}/manage-action`,
    { action: "delete", csrfToken },
    {},
    { params: cleanParams(params) },
  )

const manageCategory = async (categoryId, params, payload) =>
  await baseService.post(`/api/learning_path_categories/${categoryId}/manage-action`, payload, {}, {
    params: cleanParams(params),
  })

const getCategorySubscriptions = async (categoryId, params) =>
  await baseService.get(`/api/learning_path_categories/${categoryId}/subscriptions`, cleanParams(params))

const saveCategorySubscriptions = async (categoryId, params, payload) =>
  await baseService.put(`/api/learning_path_categories/${categoryId}/subscriptions`, payload, {
    params: cleanParams(params),
  })

/** Loads the teacher reporting overview and optional learner details. */
const getReporting = async (lpId, params = {}) =>
  await baseService.get(`/api/learning_paths/${lpId}/reporting`, cleanParams(params))

/** Resets learning path tracking for selected learners after backend validation. */
const resetReporting = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/reporting/reset`, payload, {}, {
    params: cleanParams(params),
  })

/** Recalculates quiz attempts linked to one learner in the learning path. */
const recalculateReporting = async (lpId, params, payload) =>
  await baseService.post(`/api/learning_paths/${lpId}/reporting/recalculate`, payload, {}, {
    params: cleanParams(params),
  })

/** Builds the downloadable PDF URL for the current reporting filters. */
const buildReportingPdfUrl = (lpId, params = {}) => {
  const query = new URLSearchParams(cleanParams(params))

  return `/api/learning_paths/${lpId}/reporting.pdf?${query.toString()}`
}


/** Loads the exportable learning path content items for the PDF selector. */
const getContentPdfItems = async (lpId, params = {}) =>
  await baseService.get(`/api/learning_paths/${lpId}/content-pdf/items`, cleanParams(params))

/** Builds the downloadable PDF URL for selected learning path content items. */
const buildContentPdfUrl = (lpId, params = {}, itemIds = []) => {
  const queryParams = { ...cleanParams(params) }

  if (Array.isArray(itemIds) && itemIds.length > 0) {
    queryParams.items = itemIds.map((id) => Number(id)).filter((id) => id > 0).join(",")
  }

  const query = new URLSearchParams(queryParams)

  return `/api/learning_paths/${lpId}/content.pdf?${query.toString()}`
}

/** Builds the downloadable Chamilo-native Learning Path backup URL. */
const buildChamiloBackupUrl = (lpId, params = {}) => {
  const query = new URLSearchParams(cleanParams(params))

  return `/api/learning_paths/${lpId}/chamilo-backup.zip?${query.toString()}`
}

/** Downloads the Chamilo-native backup without leaving the SPA on backend errors. */
const downloadChamiloBackup = async (lpId, params = {}) => {
  const response = await fetch(buildChamiloBackupUrl(lpId, params), {
    credentials: "same-origin",
    headers: {
      Accept: "application/zip, application/ld+json",
    },
  })

  if (!response.ok) {
    const contentType = String(response.headers.get("content-type") || "").toLowerCase()
    let message = ""

    if (contentType.includes("json")) {
      const payload = await response.json().catch(() => ({}))
      message = payload.detail || payload["hydra:description"] || payload.message || ""
    } else {
      message = (await response.text().catch(() => "")).trim()
    }

    const error = new Error(message || `Backup export failed with HTTP ${response.status}.`)
    error.status = response.status

    throw error
  }

  const blob = await response.blob()
  const disposition = String(response.headers.get("content-disposition") || "")
  const encodedMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i)
  const plainMatch = disposition.match(/filename="?([^";]+)"?/i)
  let filename = `learning-path-${lpId}.zip`

  if (encodedMatch?.[1]) {
    filename = decodeURIComponent(encodedMatch[1])
  } else if (plainMatch?.[1]) {
    filename = plainMatch[1]
  }

  return { blob, filename }
}

/** Fetches advanced-access data (users/groups restrictions) for a learning path. */
const getAdvancedAccessData = async (lpId, contextQuery) => {
  return baseService.get(`/resources/lp/${lpId}/advanced-access-data?${contextQuery}`)
}

/** Adds/updates a user advanced-access restriction. */
const saveUserAdvancedAccess = async (lpId, contextQuery, payload) => {
  return baseService.post(`/resources/lp/${lpId}/advanced-access/user?${contextQuery}`, payload)
}

/** Adds/updates a group advanced-access restriction. */
const saveGroupAdvancedAccess = async (lpId, contextQuery, payload) => {
  return baseService.post(`/resources/lp/${lpId}/advanced-access/group?${contextQuery}`, payload)
}

/** Removes a user advanced-access restriction. */
const removeUserAdvancedAccess = async (lpId, userId, contextQuery) => {
  return baseService.delete(`/resources/lp/${lpId}/advanced-access/user/${userId}?${contextQuery}`)
}

/** Removes a group advanced-access restriction. */
const removeGroupAdvancedAccess = async (lpId, groupId, contextQuery) => {
  return baseService.delete(`/resources/lp/${lpId}/advanced-access/group/${groupId}?${contextQuery}`)
}

/** Saves the selected platform classes for a learning path. */
const saveUserGroupAdvancedAccess = async (lpId, contextQuery, payload) => {
  return baseService.post(`/resources/lp/${lpId}/advanced-access/usergroups?${contextQuery}`, payload)
}

/** Clears all advanced-access date restrictions for a learning path. */
const clearAdvancedAccessDates = async (lpId, contextQuery) => {
  return baseService.post(`/resources/lp/${lpId}/advanced-access/clear-dates?${contextQuery}`, {})
}

export default {
  getLearningPaths,
  getLearningPath,
  buildRuntimeUrl,
  getRuntime,
  openRuntimeItem,
  syncRuntime,
  syncRuntimeBeacon,
  commitScormRuntime,
  commitScormRuntimeBeacon,
  restartRuntime,
  importScormPackage,
  updateScormPackage,
  buildScormPackageDownloadUrl,
  getLpCategories,
  getAiGeneratorConfiguration,
  generateAiLearningPath,
  saveAiLearningPath,
  getActionToken,
  toggleVisibility,
  toggleCategoryVisibility,
  manageLearningPath,
  reorder,
  saveLayout,
  getConfiguration,
  saveConfiguration,
  getBuilder,
  createBuilderSection,
  updateBuilderItem,
  deleteBuilderItem,
  reorderBuilderItems,
  addBuilderResource,
  updateBuilderPrerequisites,
  updateBuilderItemPrerequisite,
  updateBuilderItemAudio,
  updateBuilderBulkAuthorPrice,
  createBuilderDocument,
  updateBuilderDocument,
  saveBuilderFinalItem,
  createCategory,
  updateCategory,
  deleteCategory,
  manageCategory,
  getCategorySubscriptions,
  saveCategorySubscriptions,
  getReporting,
  resetReporting,
  recalculateReporting,
  buildReportingPdfUrl,
  getContentPdfItems,
  buildContentPdfUrl,
  buildChamiloBackupUrl,
  downloadChamiloBackup,
  getAdvancedAccessData,
  saveUserAdvancedAccess,
  saveGroupAdvancedAccess,
  removeUserAdvancedAccess,
  removeGroupAdvancedAccess,
  saveUserGroupAdvancedAccess,
  clearAdvancedAccessDates,
}

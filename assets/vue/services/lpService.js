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

/** Builds legacy VIEW URL (old student/teacher mode). */
const buildLegacyViewUrl = (
  lpId,
  { cid, sid = 0, gid = 0, node, gradebook = 0, origin = "", isStudentView = "true" } = {},
) => {
  if (!lpId) {
    console.warn("[buildLegacyViewUrl] called with empty lpId!", { lpId, cid, sid, gid })
    console.trace()
  }

  const qs = new URLSearchParams({
    action: "view",
    sid: Number(sid),
    gid: Number(gid),
    gradebook: Number(gradebook),
    origin,
    isStudentView,
  })

  if (cid !== undefined && cid !== null && String(cid) !== "" && Number(cid) !== 0) {
    qs.set("cid", cid)
  }

  if (node !== undefined && node !== null && String(node) !== "") {
    qs.set("node", node)
  }

  if (lpId) {
    qs.set("lp_id", lpId)
  }

  return `/main/lp/lp_controller.php?${qs.toString()}`
}

/**
 * Builds a generic legacy controller URL (lp_controller.php) for any action.
 *
 * Supported signatures:
 *  buildLegacyActionUrl(lpId, "report", { cid, sid, node, params })
 *  buildLegacyActionUrl("add_lp", { cid, sid, node, params }) // without lpId
 */
const buildLegacyActionUrl = (arg1, arg2, arg3 = {}) => {
  let lpId, action, opts

  if (typeof arg2 === "string") {
    lpId = arg1
    action = arg2
    opts = arg3
  } else {
    lpId = undefined
    action = arg1
    opts = arg2 || {}
  }

  const { cid, sid, node, gid = 0, gradebook = 0, origin = "", params = {} } = opts

  const search = new URLSearchParams()
  search.set("action", action)

  if (cid !== undefined && cid !== null && String(cid) !== "" && Number(cid) !== 0) {
    search.set("cid", cid)
  }

  if (lpId !== undefined && lpId !== null && String(lpId) !== "" && Number(lpId) !== 0) {
    search.set("lp_id", lpId)
  }

  // include sid even if it is 0
  if (sid !== undefined && sid !== null) {
    search.set("sid", Number.isNaN(Number(sid)) ? String(sid) : Number(sid))
  }

  search.set("gid", Number(gid))
  search.set("gradebook", Number(gradebook))
  search.set("origin", origin)

  if (node !== undefined && node !== null) {
    search.set("node", node)
  }

  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null) search.set(k, String(v))
  })

  return `/main/lp/lp_controller.php?${search.toString()}`
}

/** Build URL for updating/uploading SCORM package for an existing LP. */
const buildLegacyUploadUrl = (lpId, { cid, sid, node, gid = 0, gradebook = 0, origin = "" } = {}) => {
  return buildLegacyActionUrl(lpId, "upload", {
    cid,
    sid,
    node,
    gid,
    gradebook,
    origin,
    params: {
      // force teacher mode context
      isStudentView: "false",
    },
  })
}

/** Navigates immediately to a legacy controller action. */
const goLegacyAction = (lpId, action, opts = {}) => {
  window.location.href =
    typeof action === "string"
      ? (opts.absoluteUrl ?? false)
        ? action // allow passing a direct absolute URL
        : (opts.urlOverride ?? null) || buildLegacyActionUrl(lpId, action, opts)
      : ""
}

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

/** Persists LP display order inside the current validated context. */
const reorder = async (params, payload) => {
  await baseService.post("/api/learning_paths/reorder", payload, {}, {
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

/** Clears all advanced-access date restrictions for a learning path. */
const clearAdvancedAccessDates = async (lpId, contextQuery) => {
  return baseService.post(`/resources/lp/${lpId}/advanced-access/clear-dates?${contextQuery}`, {})
}

export default {
  getLearningPaths,
  getLearningPath,
  buildLegacyViewUrl,
  buildLegacyActionUrl,
  buildLegacyUploadUrl,
  goLegacyAction,
  getLpCategories,
  getActionToken,
  toggleVisibility,
  toggleCategoryVisibility,
  reorder,
  getAdvancedAccessData,
  saveUserAdvancedAccess,
  saveGroupAdvancedAccess,
  removeUserAdvancedAccess,
  removeGroupAdvancedAccess,
  clearAdvancedAccessDates,
}

import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"
import baseService from "./baseService"

/** Lists learning paths filtered by course/session/title. */
const getLearningPaths = async (params) => {
  const response = await axios.get(`${ENTRYPOINT}learning_paths/`, { params })

  return response.data
}

/** Fetches a learning path by ID (iid). */
const getLearningPath = async (lpId) => {
  const response = await axios.get(`${ENTRYPOINT}learning_paths/${lpId}/`)

  return response.data
}

/** Builds legacy VIEW URL (old student/teacher mode). */
const buildLegacyViewUrl = (lpId, { cid, sid, isStudentView = "true" } = {}) => {
  if (!lpId) {
    console.warn("[buildLegacyViewUrl] called with empty lpId!", { lpId, cid, sid })
    console.trace()
  }

  const qs = new URLSearchParams({ action: "view", cid, sid, isStudentView })

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

/**
 * Lists LP categories for a course (empty included).
 *
 * @param {Object} searchParams
 * @returns {Promise<Object[]>}
 */
const getLpCategories = async (searchParams) => {
  const { items } = await baseService.getCollection("/api/learning_path_categories/", searchParams)
  return items
}

export default {
  getLearningPaths,
  getLearningPath,
  buildLegacyViewUrl,
  buildLegacyActionUrl,
  buildLegacyUploadUrl,
  goLegacyAction,
  getLpCategories,
}

import baseService from "./baseService"
import { getCourseContext } from "../utils/courseContext"

/**
 * Read current course/session/group context (cid/sid/gid) from the shared helper.
 * The helper mirrors the router's resolveCourseId that feeds the cidReq store, so
 * these values stay consistent with the store, the composable and the interceptor.
 */
function courseContextParams() {
  const { cid, sid, gid } = getCourseContext()
  const out = {}
  if (cid) out.cid = cid
  if (sid) out.sid = sid
  if (gid) out.gid = gid
  return out
}

/** Merge current context and extra params (also preserve gradebook/origin from URL if present) */
function withCourseParams(params = {}) {
  const merged = { ...courseContextParams(), ...params }
  const qs = new URLSearchParams(window.location.search)
  for (const k of ["gradebook", "origin"]) {
    if (qs.has(k) && merged[k] === undefined) merged[k] = qs.get(k)
  }
  return merged
}

/** Extract a numeric or string ID from an API Platform item or IRI-looking string */
function extractId(item) {
  if (!item) return null
  if (item.iid) return item.iid
  if (item.id) return item.id
  const iriVal = item["@id"] || (typeof item === "string" ? item : null)
  if (typeof iriVal === "string") {
    const segs = iriVal.split("/")
    const last = segs.pop() || segs.pop()
    return Number(last) || last
  }
  return null
}

/** Returns a relative IRI like "/resource/1" if you ever need it (kept for compatibility) */
function iri(resource, id) {
  const res = String(resource).replace(/^\//, "")
  return `/${res}/${id}`
}

/** Resolve :node from the current route path (or ?node, or window.chamilo) */
function resolveNodeFromPath() {
  // /resources/course_maintenance/{node}/...
  let m = window.location.pathname.match(/\/resources\/course_maintenance\/(\d+)/)
  if (m && m[1]) {
    const v = Number(m[1])
    if (!Number.isNaN(v) && v > 0) return v
  }
  // /course_maintenance/{node}/...
  m = window.location.pathname.match(/\/course_maintenance\/(\d+)/)
  if (m && m[1]) {
    const v = Number(m[1])
    if (!Number.isNaN(v) && v > 0) return v
  }
  // ?node=...
  const qs = new URLSearchParams(window.location.search)
  const qv = Number(qs.get("node"))
  if (!Number.isNaN(qv) && qv > 0) return qv
  // global
  const gv = Number(window?.chamilo?.course?.resourceNodeId)
  if (!Number.isNaN(gv) && gv > 0) return gv
  return null
}

const base = {
  // Import backup
  options: (node) => `/course_maintenance/${node}/import/options`,
  upload: (node) => `/course_maintenance/${node}/import/upload`,
  serverPick: (node) => `/course_maintenance/${node}/import/server`,
  resources: (node, backupId) => `/course_maintenance/${node}/import/${backupId}/resources`,
  restore: (node, backupId) => `/course_maintenance/${node}/import/${backupId}/restore`,

  // Copy/backup/otros
  createBackup: (node) => `/course_maintenance/${node}/backup`,

  // NEW: Modern Copy Course UI
  copyOptions: (node) => `/course_maintenance/${node}/copy/options`,
  copyResources: (node) => `/course_maintenance/${node}/copy/resources`,
  copyExecute: (node) => `/course_maintenance/${node}/copy/execute`,

  // Export Moodle (.mbz)
  moodleExportOptions: (node) => `/course_maintenance/${node}/moodle/export/options`,
  moodleExportResources: (node) => `/course_maintenance/${node}/moodle/export/resources`,
  moodleExportExecute: (node) => `/course_maintenance/${node}/moodle/export/execute`,

  // Moodle import (.mbz)
  moodleImport: (node) => `/course_maintenance/${node}/moodle/import`,

  recycleCourse: (node) => `/course_maintenance/${node}/recycle`,
  recycleOptions: (node) => `/course_maintenance/${node}/recycle/options`,
  recycleResources: (node) => `/course_maintenance/${node}/recycle/resources`,
  recycleExecute: (node) => `/course_maintenance/${node}/recycle/execute`,

  deleteCourse: (node) => `/course_maintenance/${node}/delete`,

  cc13ExportOptions: (node) => `/course_maintenance/${node}/cc13/export/options`,
  cc13ExportResources: (node) => `/course_maintenance/${node}/cc13/export/resources`,
  cc13ExportExecute: (node) => `/course_maintenance/${node}/cc13/export/execute`,
  cc13Import: (node) => `/course_maintenance/${node}/cc13/import`,
}

/* =========================
   Import
   ========================= */
async function getOptions(node = resolveNodeFromPath()) {
  return baseService.get(base.options(node), withCourseParams())
}
async function uploadFile(node = resolveNodeFromPath(), file) {
  const fd = new FormData()
  fd.append("file", file, file.name || "backup.zip")
  return baseService.post(base.upload(node), fd, {}, { params: withCourseParams() })
}
async function chooseServerFile(node = resolveNodeFromPath(), filename) {
  return baseService.post(base.serverPick(node), { filename }, {}, { params: withCourseParams() })
}
async function fetchResources(node = resolveNodeFromPath(), backupId) {
  return baseService.get(base.resources(node, backupId), withCourseParams())
}
async function restoreBackup(node = resolveNodeFromPath(), backupId, { importOption, sameFileNameOption, resources }) {
  const payload = { importOption, sameFileNameOption }
  if (importOption === "select_items") payload.resources = resources || {}
  return baseService.post(base.restore(node, backupId), payload, {}, { params: withCourseParams() })
}

/* =========================
   Copy course
   ========================= */
/** Return courses list (excluding current) and defaults */
async function getCopyOptions(node = resolveNodeFromPath()) {
  return baseService.get(base.copyOptions(node), withCourseParams())
}

/** Return resource tree of the source course, same shape as import/resources */
async function fetchCopyResources(node = resolveNodeFromPath(), sourceCourseId) {
  return baseService.get(base.copyResources(node), withCourseParams({ sourceCourseId }))
}

/** Execute course copy into current course */
async function copyFromCourse(node = resolveNodeFromPath(), payload) {
  return baseService.post(base.copyExecute(node), payload, {}, { params: withCourseParams() })
}

/* =========================
   Recycle course
   ========================= */
async function getRecycleOptions(node) {
  return baseService.get(base.recycleOptions(node), withCourseParams())
}
async function fetchRecycleResources(node) {
  return baseService.get(base.recycleResources(node), withCourseParams())
}
async function recycleExecute(node, payload) {
  return baseService.post(base.recycleExecute(node), payload, {}, { params: withCourseParams() })
}

/* =========================
   Other endpoints
   ========================= */
async function createBackup(node = resolveNodeFromPath(), scope = "full") {
  return baseService.post(base.createBackup(node), { scope }, {}, { params: withCourseParams() })
}

/** Compatibility: prior version that POSTed raw payload to /copy */
async function copyCourse(node = resolveNodeFromPath(), payload) {
  return baseService.post(`/course_maintenance/${node}/copy`, payload, {}, { params: withCourseParams() })
}

async function recycleCourse(node = resolveNodeFromPath(), payload) {
  return baseService.post(base.recycleCourse(node), payload, {}, { params: withCourseParams() })
}
async function deleteCourse(node = resolveNodeFromPath(), payloadOrConfirm) {
  const payload = typeof payloadOrConfirm === "string" ? { confirm: payloadOrConfirm } : payloadOrConfirm || {}

  return baseService.post(base.deleteCourse(node), payload, {}, { params: withCourseParams() })
}

// -------- Moodle export --------
async function moodleExportOptions(node = resolveNodeFromPath()) {
  return baseService.get(base.moodleExportOptions(node), withCourseParams())
}

async function moodleExportResources(node = resolveNodeFromPath()) {
  return baseService.get(base.moodleExportResources(node), withCourseParams())
}

async function moodleExportExecute(node = resolveNodeFromPath(), payload) {
  const resp = await baseService.postRaw(base.moodleExportExecute(node), payload, {
    params: withCourseParams(),
    responseType: "blob",
    validateStatus: () => true,
  })

  const ct = String(resp.headers["content-type"] || "")
  const cd = String(resp.headers["content-disposition"] || "")

  if (ct.includes("application/json")) {
    const json = await new Response(resp.data).json().catch(() => ({}))
    if (resp.status >= 400) {
      const err = new Error(json?.error || "Request failed")
      err.response = { status: resp.status, data: json }
      throw err
    }
    return json
  }

  if (ct.includes("application/zip") || ct.includes("application/octet-stream")) {
    let filename = "backup.mbz"
    const m = cd.match(/filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/)
    if (m) filename = decodeURIComponent(m[1] || m[2] || filename)

    const url = URL.createObjectURL(resp.data)
    const a = document.createElement("a")
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)

    return { ok: true, message: "Download started", filename }
  }

  const err = new Error("Unexpected response type")
  err.response = { status: resp.status }
  throw err
}

async function importFromMoodle(node = resolveNodeFromPath(), file) {
  if (!file) throw new Error("Missing .mbz file")
  const fd = new FormData()
  fd.append("file", file, file.name || "backup.mbz")
  return baseService.post(base.moodleImport(node), fd, {}, { params: withCourseParams() })
}

// CC 1.3 export
async function cc13ExportOptions(node = resolveNodeFromPath()) {
  return baseService.get(base.cc13ExportOptions(node), withCourseParams())
}
async function cc13ExportResources(node = resolveNodeFromPath()) {
  return baseService.get(base.cc13ExportResources(node), withCourseParams())
}
async function cc13ExportExecute(node = resolveNodeFromPath(), payload) {
  // { ok, file, downloadUrl, message }
  return baseService.post(
    base.cc13ExportExecute(node),
    payload,
    {},
    {
      params: withCourseParams(),
    },
  )
}

// CC 1.3 import
async function cc13Import(node = resolveNodeFromPath(), fileOrOptions) {
  // File upload path
  if (typeof File !== "undefined" && fileOrOptions instanceof File) {
    const fd = new FormData()
    fd.append("file", fileOrOptions, fileOrOptions.name || "package.imscc")
    const resp = await baseService.postRaw(base.cc13Import(node), fd, {
      params: withCourseParams(),
      validateStatus: () => true,
      responseType: "json",
    })
    if (resp.status >= 400) {
      const msg = resp.data?.error || resp.data?.message || "Import failed"
      const err = new Error(msg)
      err.response = { status: resp.status, data: resp.data }
      throw err
    }
    return resp.data // { ok:true, message:"..." }
  }

  // Optional JSON mode (if later you add server switches)
  return baseService.post(base.cc13Import(node), fileOrOptions || {}, {}, { params: withCourseParams() })
}

/* =========================
   Export
   ========================= */
export { withCourseParams, courseContextParams, extractId, iri, resolveNodeFromPath }

export default {
  // Import
  getOptions,
  uploadFile,
  chooseServerFile,
  fetchResources,
  restoreBackup,

  // Copy
  getCopyOptions,
  fetchCopyResources,
  copyFromCourse,
  copyCourse,

  // Recycle
  getRecycleOptions,
  fetchRecycleResources,
  recycleExecute,

  // Others
  createBackup,
  recycleCourse,
  deleteCourse,

  moodleExportOptions,
  moodleExportResources,
  moodleExportExecute,

  importFromMoodle,

  cc13ExportOptions,
  cc13ExportResources,
  cc13ExportExecute,
  cc13Import,
}

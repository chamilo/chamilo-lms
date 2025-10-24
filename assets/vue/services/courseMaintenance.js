import axios from "axios"
import { useCidReqStore } from "../store/cidReq"

// If the frontend is served from the same origin, this keeps requests relative.
const http = axios.create({
  baseURL: "/", // same-origin root
})

/** Read current course/session/group context from Pinia store with graceful fallbacks */
function courseContextParams() {
  const store = useCidReqStore()

  const fromStore = {
    cid: Number(store?.course?.id) || null,
    sid: Number(store?.session?.id) || null,
    gid: Number(store?.group?.id) || null,
  }

  // Fallback to querystring (useful when reloading or opening deep links)
  const qs = new URLSearchParams(window.location.search)
  const pickNum = (...names) => {
    for (const n of names) {
      const v = qs.get(n)
      if (v !== null && v !== undefined && !Number.isNaN(Number(v))) return Number(v)
    }
    return null
  }

  return {
    ...(fromStore.cid ? { cid: fromStore.cid } : {}),
    ...(fromStore.sid ? { sid: fromStore.sid } : {}),
    ...(fromStore.gid ? { gid: fromStore.gid } : {}),
    ...(fromStore.cid ? {} : pickNum("cid", "cidReq") ? { cid: pickNum("cid", "cidReq") } : {}),
    ...(fromStore.sid ? {} : pickNum("sid", "id_session") ? { sid: pickNum("sid", "id_session") } : {}),
    ...(fromStore.gid ? {} : pickNum("gid", "gidReq") ? { gid: pickNum("gid", "gidReq") } : {}),
  }
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
  const resp = await http.get(base.options(node), { params: withCourseParams() })
  return resp.data
}
async function uploadFile(node = resolveNodeFromPath(), file) {
  const fd = new FormData()
  fd.append("file", file, file.name || "backup.zip")
  const resp = await http.post(base.upload(node), fd, {
    headers: { "Content-Type": "multipart/form-data" },
    params: withCourseParams(),
  })
  return resp.data
}
async function chooseServerFile(node = resolveNodeFromPath(), filename) {
  const resp = await http.post(base.serverPick(node), { filename }, { params: withCourseParams() })
  return resp.data
}
async function fetchResources(node = resolveNodeFromPath(), backupId) {
  const resp = await http.get(base.resources(node, backupId), { params: withCourseParams() })
  return resp.data
}
async function restoreBackup(node = resolveNodeFromPath(), backupId, { importOption, sameFileNameOption, resources }) {
  const payload = { importOption, sameFileNameOption }
  if (importOption === "select_items") payload.resources = resources || {}
  const resp = await http.post(base.restore(node, backupId), payload, { params: withCourseParams() })
  return resp.data
}

/* =========================
   Copy course
   ========================= */
/** Return courses list (excluding current) and defaults */
async function getCopyOptions(node = resolveNodeFromPath()) {
  const resp = await http.get(base.copyOptions(node), {
    params: withCourseParams(),
  })
  return resp.data
}

/** Return resource tree of the source course, same shape as import/resources */
async function fetchCopyResources(node = resolveNodeFromPath(), sourceCourseId) {
  const resp = await http.get(base.copyResources(node), {
    params: withCourseParams({ sourceCourseId }),
  })
  return resp.data
}

/** Execute course copy into current course */
async function copyFromCourse(node = resolveNodeFromPath(), payload) {
  const resp = await http.post(base.copyExecute(node), payload, {
    params: withCourseParams(),
  })
  return resp.data
}

/* =========================
   Recycle course
   ========================= */
async function getRecycleOptions(node) {
  const r = await http.get(base.recycleOptions(node), { params: withCourseParams() })
  return r.data
}
async function fetchRecycleResources(node) {
  const r = await http.get(base.recycleResources(node), { params: withCourseParams() })
  return r.data
}
async function recycleExecute(node, payload) {
  const r = await http.post(base.recycleExecute(node), payload, { params: withCourseParams() })
  return r.data
}

/* =========================
   Other endpoints
   ========================= */
async function createBackup(node = resolveNodeFromPath(), scope = "full") {
  const resp = await http.post(base.createBackup(node), { scope }, { params: withCourseParams() })
  return resp.data
}

/** Compatibility: prior version that POSTed raw payload to /copy */
async function copyCourse(node = resolveNodeFromPath(), payload) {
  const resp = await http.post(`/course_maintenance/${node}/copy`, payload, { params: withCourseParams() })
  return resp.data
}

async function recycleCourse(node = resolveNodeFromPath(), payload) {
  const resp = await http.post(base.recycleCourse(node), payload, { params: withCourseParams() })
  return resp.data
}
async function deleteCourse(node = resolveNodeFromPath(), confirmText) {
  const resp = await http.post(base.deleteCourse(node), { confirm: confirmText }, { params: withCourseParams() })
  return resp.data
}

// -------- Moodle export --------
async function moodleExportOptions(node = resolveNodeFromPath()) {
  const resp = await http.get(base.moodleExportOptions(node), { params: withCourseParams() })
  return resp.data
}

async function moodleExportResources(node = resolveNodeFromPath()) {
  const resp = await http.get(base.moodleExportResources(node), { params: withCourseParams() })
  return resp.data
}

async function moodleExportExecute(node = resolveNodeFromPath(), payload) {
  const resp = await http.post(base.moodleExportExecute(node), payload, {
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
  const resp = await http.post(base.moodleImport(node), fd, {
    headers: { "Content-Type": "multipart/form-data" },
    params: withCourseParams(),
  })
  return resp.data
}

// CC 1.3 export
async function cc13ExportOptions(node = resolveNodeFromPath()) {
  const resp = await http.get(base.cc13ExportOptions(node), { params: withCourseParams() })
  return resp.data
}
async function cc13ExportResources(node = resolveNodeFromPath()) {
  const resp = await http.get(base.cc13ExportResources(node), { params: withCourseParams() })
  return resp.data
}
async function cc13ExportExecute(node = resolveNodeFromPath(), payload) {
  const resp = await http.post(base.cc13ExportExecute(node), payload, {
    params: withCourseParams(),
    headers: { Accept: "application/json" },
  })
  return resp.data // { ok, file, downloadUrl, message }
}

// CC 1.3 import
async function cc13Import(node = resolveNodeFromPath(), fileOrOptions) {
  // File upload path
  if (typeof File !== "undefined" && fileOrOptions instanceof File) {
    const fd = new FormData()
    fd.append("file", fileOrOptions, fileOrOptions.name || "package.imscc")
    const resp = await http.post(base.cc13Import(node), fd, {
      headers: { "Content-Type": "multipart/form-data" },
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
  const resp = await http.post(base.cc13Import(node), fileOrOptions || {}, {
    params: withCourseParams(),
  })
  return resp.data
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

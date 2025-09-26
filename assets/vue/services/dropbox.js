import axios from "axios"
import { useCidReq } from "../composables/cidReq"

const BASE = "/dropbox"

function buildQuery(extra = {}, overrideCtx = {}) {
  let qCid, qSid, qGid
  if (typeof window !== "undefined") {
    const sp = new URLSearchParams(window.location.search || "")
    qCid = sp.get("cid")
    qSid = sp.get("sid")
    qGid = sp.get("gid")
  }
  let fromComp = {}
  if (typeof useCidReq === "function") {
    try { fromComp = useCidReq() || {} } catch {}
  }
  const cid = overrideCtx.cid ?? qCid ?? fromComp.cid
  const sid = overrideCtx.sid ?? qSid ?? fromComp.sid ?? 0
  const gid = overrideCtx.gid ?? qGid ?? fromComp.gid ?? 0

  const params = new URLSearchParams({
    cid: String(cid ?? ""),
    sid: String(sid ?? 0),
    gid: String(gid ?? 0),
    ...Object.fromEntries(Object.entries(extra ?? {}).map(([k, v]) => [k, String(v)])),
  })
  return params.toString()
}

/** ------- Categories ------- */
async function listCategories({ area }) {
  const qs = buildQuery({ area })
  const { data } = await axios.get(`${BASE}/categories?${qs}`)
  return data
}
async function createCategory({ title, area }) {
  const qs = buildQuery()
  const { data } = await axios.post(`${BASE}/categories?${qs}`, { title, area })
  return data
}
async function renameCategory({ id, title, area }) {
  const qs = buildQuery()
  const { data } = await axios.patch(`${BASE}/categories/${id}?${qs}`, { title, area })
  return data
}
async function deleteCategory({ id, area }) {
  const qs = buildQuery({ area })
  const { data } = await axios.delete(`${BASE}/categories/${id}?${qs}`)
  return data
}

/** ------- Files (list/move/delete) ------- */
async function listFiles({ area, categoryId = 0 }) {
  const qs = buildQuery({ area, categoryId })
  const { data } = await axios.get(`${BASE}/files?${qs}`)
  return data
}
async function moveFile({ id, targetCatId, area }) {
  const qs = buildQuery()
  const { data } = await axios.patch(`${BASE}/files/${id}/move?${qs}`, { targetCatId, area })
  return data
}
async function deleteFiles(ids, area) {
  const qs = buildQuery()
  const { data } = await axios.delete(`${BASE}/files?${qs}`, { data: { ids, area } })
  return data
}

/** ------- Feedback ------- */
async function listFeedback(id) {
  const qs = buildQuery()
  const { data } = await axios.get(`${BASE}/files/${id}/feedback?${qs}`)
  return data
}
async function createFeedback(id, text) {
  const qs = buildQuery()
  const { data } = await axios.post(`${BASE}/files/${id}/feedback?${qs}`, { text })
  return data
}

/** ------- Recipients ------- */
async function listRecipients() {
  const qs = buildQuery()
  const { data } = await axios.get(`${BASE}/recipients?${qs}`)
  return data
}

/** ------- Single file ------- */
async function getFile(id) {
  const qs = buildQuery()
  const { data } = await axios.get(`${BASE}/files/${id}?${qs}`)
  return data
}

/** ------- Update file ------- */
/**
 * @param {{id:number, file?: Blob|File|{data:Blob,name?:string}|null, categoryId?: number|null, renameTitle?: boolean}} args
 */
async function updateFile({ id, file = null, categoryId = null, renameTitle = false, newTitle = "" }) {
  const qs = buildQuery()
  const fd = new FormData()

  if (file) {
    const blob =
      file instanceof Blob
        ? file
        : file?.data instanceof Blob
          ? file.data
          : null
    if (!blob) throw new Error("Invalid file object: expected Blob/File or Uppy file with .data")
    const name = file?.name || "upload.bin"
    fd.append("newFile", blob, name)
    fd.append("renameTitle", renameTitle ? "1" : "0")
  }
  if (categoryId != null) {
    fd.append("categoryId", String(categoryId))
  }
  if (renameTitle && newTitle) {
    fd.append("newTitle", newTitle)
  }

  const { data } = await axios.post(`${BASE}/files/${id}/update?${qs}`, fd)
  return data
}

/** ------- Upload / Download ------- */
export async function uploadFile({ file, description, overwrite, recipients, area, context, parentResourceNodeId }) {
  const sp = typeof window !== "undefined" ? new URLSearchParams(window.location.search) : new URLSearchParams("")
  const cid = Number(context?.cid ?? (sp.get("cid") || 0))
  const sid = Number(context?.sid ?? (sp.get("sid") || 0))
  const gid = Number(context?.gid ?? (sp.get("gid") || 0))
  if (!cid) throw new Error("cid missing or invalid")

  const fd = new FormData()
  fd.append("uploadFile", file, file?.name || "upload.bin")
  fd.append("filetype", "file")
  fd.append("description", description || "")
  fd.append("fileExistsOption", overwrite ? "overwrite" : "")
  ;(recipients || []).forEach((r) => fd.append("recipients[]", String(r)))
  if (parentResourceNodeId != null) fd.append("parentResourceNodeId", String(parentResourceNodeId))

  return axios.post(`/api/c_dropbox_files/upload?cid=${cid}&sid=${sid}&gid=${gid}`, fd)
}

function downloadUrl(id) {
  const qs = buildQuery()
  return `${BASE}/files/${id}/download?${qs}`
}

function categoryZipUrl(id, area = "sent") {
  const qs = buildQuery({ area })
  return `${BASE}/categories/${id}/zip?${qs}`
}

export default {
  listCategories,
  createCategory,
  renameCategory,
  deleteCategory,
  listFiles,
  moveFile,
  deleteFiles,
  listFeedback,
  createFeedback,
  listRecipients,
  uploadFile,
  downloadUrl,
  getFile,
  updateFile,
  categoryZipUrl,
}

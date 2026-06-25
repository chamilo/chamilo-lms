import makeService, { asResponse, toServiceError } from "./api"
import baseService from "./baseService"
import prettyBytes from "pretty-bytes"
import api from "../config/api"
import { useCidReqStore } from "../store/cidReq"
import { getCourseContext } from "../utils/courseContext"

const oldService = makeService("documents")

function normalizeCode(code) {
  return String(code || "")
    .trim()
    .toLowerCase()
}

/**
 * Convert payload.searchFieldValues object into flat FormData keys:
 * - searchFieldValues[t] = "..."
 * - searchFieldValues[d] = "..."
 *
 * This prevents "[object Object]" from being sent to the backend when using FormData.
 */
function flattenSearchFieldValues(payload) {
  if (!payload || typeof payload !== "object") {
    return payload
  }

  const normalized = { ...payload }
  const sfv = normalized.searchFieldValues

  // Remove the original object to avoid FormData -> "[object Object]"
  if (sfv && typeof sfv === "object" && !Array.isArray(sfv)) {
    delete normalized.searchFieldValues

    Object.entries(sfv).forEach(([code, val]) => {
      const c = normalizeCode(code)
      if (!c) return
      normalized[`searchFieldValues[${c}]`] = String(val ?? "")
    })
  }

  return normalized
}

function buildFormData(payload) {
  const fd = new FormData()

  Object.entries(payload || {}).forEach(([key, val]) => {
    if (undefined === val || null === val) return
    fd.append(key, val)
  })

  return fd
}

// ----------------------------
// Quota helpers (shared)
// ----------------------------

// Default threshold so it is easy to see in UI.
const DEFAULT_QUOTA_WARNING_THRESHOLD_PERCENT = 2
const DEFAULT_QUOTA_STALE_MS = 30_000

// In-memory cache per (courseId, sid, gid)
const quotaCache = new Map()

function quotaCacheKey(courseId, sid, gid) {
  return `${Number(courseId) || 0}:${Number(sid) || 0}:${Number(gid) || 0}`
}

/**
 * Fetch quota usage for a course. Returns:
 * { availableBytes, availablePercent, fetchedAt } or null
 */
async function getQuotaUsage(courseId, { sid = 0, gid = 0, force = false, staleMs = DEFAULT_QUOTA_STALE_MS } = {}) {
  const cid = Number(courseId || 0)
  if (!cid) return null

  const s = Number(sid || 0)
  const g = Number(gid || 0)

  const key = quotaCacheKey(cid, s, g)
  const now = Date.now()

  const cached = quotaCache.get(key)
  if (!force && cached?.fetchedAt && now - cached.fetchedAt < staleMs) {
    return cached
  }

  try {
    const json = await baseService.get(`/api/documents/${cid}/usage`, { sid: s, gid: g })
    const quota = json?.quota || {}
    const availableBytes = Number(quota.availableBytes)
    const availablePercent = Number(quota.availablePercent)

    if (!Number.isFinite(availableBytes) || !Number.isFinite(availablePercent)) {
      return null
    }

    const info = { availableBytes, availablePercent, fetchedAt: now }
    quotaCache.set(key, info)
    return info
  } catch (e) {
    console.error("[DocumentsService] Failed to fetch quota usage:", e)
    return null
  }
}

function formatBytesAsMb(bytes) {
  const n = Number(bytes || 0)

  if (!Number.isFinite(n)) {
    return "0 MB"
  }

  const mb = Math.max(n, 0) / 1048576
  const rounded = Math.round(mb * 100) / 100

  if (Number.isInteger(rounded)) {
    return `${rounded} MB`
  }

  return `${String(rounded).replace(/\.0+$/, "").replace(/(\.\d*?)0+$/, "$1")} MB`
}

/**
 * Build "Available space (%s)" message using MB, because course document quotas
 * are configured and stored in MB.
 */
function formatAvailableSpaceMessage(t, availableBytes) {
  const template = String(t?.("Available space (%s)") ?? "Available space (%s)")
  const bytesLabel = formatBytesAsMb(availableBytes)

  return template.replace("%s", bytesLabel)
}

/**
 * Return warning message (string) if quota is below/equals threshold.
 * Otherwise returns "".
 */
function getQuotaWarningMessage(t, quotaInfo, { thresholdPercent = DEFAULT_QUOTA_WARNING_THRESHOLD_PERCENT } = {}) {
  const ap = Number(quotaInfo?.availablePercent)
  const ab = Number(quotaInfo?.availableBytes)

  if (!Number.isFinite(ap) || !Number.isFinite(ab)) return ""

  if (ap <= Number(thresholdPercent)) {
    return formatAvailableSpaceMessage(t, ab)
  }

  return ""
}

/**
 * Convenience: fetch usage + compute message.
 */
async function fetchQuotaWarningMessage(
  t,
  courseId,
  { sid = 0, gid = 0, force = false, thresholdPercent = DEFAULT_QUOTA_WARNING_THRESHOLD_PERCENT } = {},
) {
  const info = await getQuotaUsage(courseId, { sid, gid, force })
  return getQuotaWarningMessage(t, info, { thresholdPercent })
}

/**
 * Extract a meaningful error message from API responseText (Uppy or others).
 */
function extractApiErrorMessageFromText(responseText) {
  if (!responseText) return ""

  try {
    const json = JSON.parse(responseText)
    const msg =
      json?.error ||
      json?.message ||
      json?.detail ||
      json?.["hydra:description"] ||
      (Array.isArray(json?.violations) && json.violations.length ? json.violations[0].message : null)

    return String(msg || "")
  } catch {
    return String(responseText || "")
  }
}

/**
 * Detect quota errors by status + message.
 */
function isQuotaError(status, message) {
  const s = Number(status || 0)
  const m = String(message || "").toLowerCase()

  if ([507, 413, 422, 400].includes(s)) {
    if (m.includes("not enough space")) return true
    if (m.includes("there is not enough space")) return true
    if (m.includes("quota")) return true
    if (m.includes("disk") && m.includes("space")) return true
  }

  if (m.includes("there is not enough space")) return true
  if (m.includes("not enough space")) return true
  if (m.includes("quota")) return true

  return false
}

/**
 * Standard quota message used across UI.
 */
function getQuotaUploadErrorMessage(t) {
  return String(
    t?.("There is not enough space to upload this file.") ?? "There is not enough space to upload this file.",
  )
}

export default {
  ...oldService,

  // ----------------------------
  // Existing overrides
  // ----------------------------

  /**
   * Override createWithFormData only for documents to avoid breaking other modules.
   * Two reasons:
   * 1. Flattens searchFieldValues so FormData does not serialize them as "[object Object]".
   * 2. Forces the current course/session/group context (cid/sid/gid) onto the POST URL.
   *    The shared axios interceptor in config/api.js reads getRawCourseContext() from
   *    window.location.search, which is empty when the SPA navigates without preserving
   *    ?cid=. Without cid in the request, CidReqListener wipes the session course;
   *    CreateDocumentFileAction then builds a resource_link with no cid, and the new
   *    document hangs orphaned (not visible in the course documents list). Reading the
   *    Pinia cidReq store directly here is the canonical source maintained by the
   *    router guards and survives URL changes.
   */
  async createWithFormData(payload) {
    const prepared = flattenSearchFieldValues(payload)
    const formData = buildFormData(prepared)

    // Course context: Pinia store is authoritative; getCourseContext() (URL-based)
    // is the fallback for early init before the store is hydrated.
    let cid = 0
    let sid = 0
    let gid = 0
    try {
      const store = useCidReqStore()
      cid = Number(store.course?.id ?? 0) || 0
      sid = Number(store.session?.id ?? 0) || 0
      gid = Number(store.group?.id ?? 0) || 0
    } catch {
      // Pinia not active (test or pre-mount) — fall through to URL parse.
    }
    if (!cid) {
      const fromUrl = getCourseContext()
      cid = fromUrl.cid
      sid = fromUrl.sid
      gid = fromUrl.gid
    }

    const params = {}
    if (cid > 0) params.cid = cid
    if (sid > 0) params.sid = sid
    if (gid > 0) params.gid = gid

    try {
      const response = await api.post("/api/documents", formData, { params })
      return asResponse(response)
    } catch (error) {
      throw toServiceError(error)
    }
  },

  /**
   * IMPORTANT:
   * PHP/Symfony does not parse multipart/form-data on PUT requests.
   * So for updates we send POST with a method override to PUT.
   */
  async updateWithFormData(payload) {
    const prepared = flattenSearchFieldValues(payload)
    const iri = prepared?.["@id"] || payload?.["@id"]

    if (!iri) {
      throw new Error("[Documents] updateWithFormData: missing @id in payload.")
    }

    const bodyPayload = { ...prepared }
    delete bodyPayload["@id"]
    delete bodyPayload["@context"]
    delete bodyPayload["@type"]

    const fd = buildFormData(bodyPayload)
    fd.append("_method", "PUT")

    // PHP/Symfony does not parse multipart/form-data on PUT, so POST with a
    // method override. baseService sends FormData as multipart automatically.
    // Returns a Response-like shim because the CRUD store calls response.json().
    try {
      const response = await baseService.postRaw(iri, fd, { headers: { "X-HTTP-Method-Override": "PUT" } })

      return asResponse(response)
    } catch (error) {
      throw toServiceError(error)
    }
  },

  async createCloudLink(payload) {
    try {
      return await baseService.post("/api/documents", { ...payload, filetype: "link" })
    } catch (error) {
      const data = error?.response?.data || {}
      const message =
        data?.message ||
        data?.detail ||
        data?.["hydra:description"] ||
        (Array.isArray(data?.violations) && data.violations.length ? data.violations[0].message : null) ||
        "Unable to create cloud link."

      throw new Error(message, { cause: error })
    }
  },

  /**
   * Retrieves all document templates for a given course.
   */
  getTemplates: async (courseId) => {
    return baseService.get(`/template/all-templates/${courseId}`)
  },

  /**
   * Creates a document (folder or file) from a FormData payload.
   * Supports upload progress and abortion through the options bag.
   */
  async uploadDocumentFile(formData, { signal, onUploadProgress } = {}) {
    return baseService.post("/api/documents", formData, {}, { signal, onUploadProgress })
  },

  /**
   * Fetches the raw text content of a file URL (e.g. an SVG contentUrl).
   * @param {string} url
   * @returns {Promise<string>}
   */
  async fetchTextContent(url) {
    const response = await baseService.getRaw(url, { responseType: "text" })

    return response.data
  },

  /**
   * Lists documents matching the given query params (collection endpoint).
   */
  async listDocuments(params = {}) {
    return baseService.getCollection("/api/documents", params)
  },

  /**
   * Fetches a single document by its IRI.
   */
  async getDocumentByIri(iri) {
    return baseService.get(iri)
  },

  /**
   * Downloads all documents under a root node as a ZIP blob.
   * @param {number|string} rootNodeId
   * @param {Object} [params={}]
   * @returns {Promise<Blob>}
   */
  async downloadAll(rootNodeId, params = {}) {
    const response = await baseService.postRaw(
      "/api/documents/download-all",
      { rootNodeId },
      { responseType: "blob", params },
    )

    return response.data
  },

  /**
   * Downloads the given document ids as a ZIP blob.
   * @param {Array<number|string>} ids
   * @returns {Promise<Blob>}
   */
  async downloadSelected(ids) {
    const response = await baseService.postRaw("/api/documents/download-selected", { ids }, { responseType: "blob" })

    return response.data
  },

  /**
   * Checks whether a document is used in learning paths.
   * @param {number|string} iid
   * @returns {Promise<Object>}
   */
  async getLpUsage(iid) {
    return baseService.get(`/api/documents/${iid}/lp-usage`)
  },

  /**
   * Deletes a document.
   * @param {number|string} iid
   * @returns {Promise<any>}
   */
  async deleteDocument(iid) {
    return baseService.delete(`/api/documents/${iid}`)
  },

  /**
   * Fetches the storage quota usage for a course.
   * @param {number|string} courseId
   * @param {Object} [params={}]
   * @returns {Promise<Object>}
   */
  async getUsage(courseId, params = {}) {
    return baseService.get(`/api/documents/${courseId}/usage`, params)
  },

  /**
   * Moves a document to another parent node.
   * @param {number|string} iid
   * @param {number|string} parentResourceNodeId
   * @param {Object} [params={}]
   * @returns {Promise<Object>}
   */
  async moveDocument(iid, parentResourceNodeId, params = {}) {
    return baseService.put(`/api/documents/${iid}/move`, { parentResourceNodeId }, { params })
  },

  /**
   * Replaces a document file.
   * @param {number|string} iid
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  async replaceDocument(iid, formData) {
    return baseService.post(`/api/documents/${iid}/replace`, formData)
  },

  /**
   * Checks whether a document is a template.
   * @param {number|string} documentId
   * @returns {Promise<Object>}
   */
  async isDocumentTemplate(documentId) {
    return baseService.get(`/template/document-templates/${documentId}/is-template`)
  },

  /**
   * Deletes a document template.
   * @param {number|string} documentId
   * @returns {Promise<any>}
   */
  async deleteDocumentTemplate(documentId) {
    return baseService.post(`/template/document-templates/${documentId}/delete`)
  },

  /**
   * Creates a document template from a FormData payload.
   * @param {FormData} formData
   * @returns {Promise<Object>}
   */
  async createDocumentTemplate(formData) {
    return baseService.post("/template/document-templates/create", formData)
  },

  // ----------------------------
  // Quota API (shared)
  // ----------------------------
  getQuotaUsage,
  formatAvailableSpaceMessage,
  getQuotaWarningMessage,
  fetchQuotaWarningMessage,
  extractApiErrorMessageFromText,
  isQuotaError,
  getQuotaUploadErrorMessage,
}

import fetch from "../utils/fetch"
import makeService from "./api"
import baseService from "./baseService"
import { ENTRYPOINT } from "../config/entrypoint"
import prettyBytes from "pretty-bytes"

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
    const url = `${ENTRYPOINT}documents/${cid}/usage?sid=${s}&gid=${g}`
    const response = await window.fetch(url, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })

    if (!response.ok) return null

    const json = await response.json()
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

/**
 * Build "Available space (%s)" message using i18n + prettyBytes.
 * Vue i18n does not format "%s", so we replace it manually.
 */
function formatAvailableSpaceMessage(t, availableBytes) {
  const template = String(t?.("Available space (%s)") ?? "Available space (%s)")
  const bytesLabel = prettyBytes(Math.max(Number(availableBytes || 0), 0))
  return template.includes("%s") ? template.replace("%s", bytesLabel) : `${template} (${bytesLabel})`
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
   * This keeps api.js untouched and prevents sending searchFieldValues as "[object Object]".
   */
  createWithFormData(payload) {
    const prepared = flattenSearchFieldValues(payload)
    return oldService.createWithFormData(prepared)
  },

  /**
   * IMPORTANT:
   * PHP/Symfony does not parse multipart/form-data on PUT requests.
   * So for updates we send POST with a method override to PUT.
   */
  updateWithFormData(payload) {
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

    return fetch(iri, {
      method: "POST",
      body: fd,
      headers: {
        "X-HTTP-Method-Override": "PUT",
      },
    })
  },

  /**
   * Retrieves all document templates for a given course.
   */
  getTemplates: async (courseId) => {
    return baseService.get(`/template/all-templates/${courseId}`)
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

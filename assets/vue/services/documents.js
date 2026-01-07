import fetch from "../utils/fetch"
import makeService from "./api"
import baseService from "./baseService"

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

export default {
  ...oldService,

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
   * This guarantees searchFieldValues[...] arrives in Request->get('searchFieldValues') on the backend.
   */
  updateWithFormData(payload) {
    const prepared = flattenSearchFieldValues(payload)
    const iri = prepared?.["@id"] || payload?.["@id"]

    if (!iri) {
      throw new Error("[Documents] updateWithFormData: missing @id in payload.")
    }

    // Do not send @id as form field
    const bodyPayload = { ...prepared }
    delete bodyPayload["@id"]
    delete bodyPayload["@context"]
    delete bodyPayload["@type"]

    const fd = buildFormData(bodyPayload)

    // Method override (server will see PUT, but PHP will still parse the POST body)
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
   *
   * @param {string} courseId - The ID of the course.
   * @returns {Promise}
   */
  getTemplates: async (courseId) => {
    return baseService.get(`/template/all-templates/${courseId}`)
  },
}

import api from "../config/api"
import SubmissionError from "../error/SubmissionError"
import { normalize } from "../utils/hydra"

// Legacy CRUD service factory. It used to talk to the backend through the
// native-fetch wrapper (utils/fetch.js); it now goes through the shared axios
// instance so it gets the same interceptors (Accept negotiation, cid/sid/gid
// injection, error handling) as the rest of the app.
//
// To stay backward compatible, every method still resolves to a Response-like
// object exposing `.json()` / `.status` / `.ok`, which is what the CRUD store
// (store/modules/crud.js) and other callers expect.

/**
 * Resolves a resource name or IRI to an absolute API Platform path.
 * @param {string} idOrEndpoint
 * @returns {string}
 */
function toApiUrl(idOrEndpoint) {
  const value = String(idOrEndpoint)

  return value.startsWith("/api/") ? value : `/api/${value}`
}

/**
 * Wraps an axios response in a minimal Response-like object so existing callers
 * that rely on `response.json()` / `response.status` keep working.
 * @param {import("axios").AxiosResponse} response
 * @returns {{ok: boolean, status: number, statusText: string, headers: Object, json: () => Promise<any>}}
 */
export function asResponse(response) {
  return {
    ok: response.status >= 200 && response.status < 300,
    status: response.status,
    statusText: response.statusText,
    headers: response.headers,
    json: async () => response.data,
  }
}

/**
 * Converts an axios error into the Error/SubmissionError shape the legacy fetch
 * wrapper threw, so form violation handling (handleError) keeps working.
 * @param {any} error
 * @returns {Error}
 */
export function toServiceError(error) {
  const data = error?.response?.data

  if (data && "object" === typeof data) {
    if (Array.isArray(data.violations) && data.violations.length) {
      const errors = { _error: data["hydra:description"] || "An error occurred." }
      data.violations.forEach((violation) => {
        errors[violation.propertyPath] = violation.message
      })

      return new SubmissionError(errors)
    }

    const message =
      data.error || data["hydra:description"] || data["hydra:title"] || (401 === data.code ? "Not allowed" : null)

    if (message) {
      return new Error(message)
    }
  }

  return error instanceof Error ? error : new Error("An error occurred.")
}

// As stated here https://github.com/chamilo/chamilo-lms/pull/5386#discussion_r1578471409
// prefer assets/vue/services/baseService.js directly for new code
// (see assets/vue/services/socialService.js for an example).
export default function makeService(endpoint, extensions = {}) {
  const baseService = {
    async find(id, params) {
      const currentParams = new URLSearchParams(window.location.search)
      const combinedParams = {
        ...Object.fromEntries(currentParams),
        ...params,
        getFile: true,
      }

      try {
        const response = await api.get(toApiUrl(id), { params: combinedParams })

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    async findAll(options = {}) {
      try {
        const response = await api.get(toApiUrl(endpoint), { params: options.params || {} })

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    async createWithFormData(payload) {
      const formData = new FormData()

      if (payload) {
        Object.keys(payload).forEach((key) => {
          formData.append(key, payload[key])
        })
      }

      try {
        const response = await api.post(toApiUrl(endpoint), formData)

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    async create(payload) {
      try {
        const response = await api.post(toApiUrl(endpoint), payload)

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    async del(item) {
      try {
        const response = await api.delete(toApiUrl(item["@id"]))

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    async updateWithFormData(payload) {
      try {
        const response = await api.put(toApiUrl(payload["@id"]), normalize(payload))

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    async update(payload) {
      try {
        const response = await api.put(toApiUrl(payload["@id"]), normalize(payload))

        return asResponse(response)
      } catch (error) {
        throw toServiceError(error)
      }
    },
    handleError(error, errorsRef, violationsRef) {
      if (error instanceof SubmissionError) {
        violationsRef.value = error.errors
        errorsRef.value = error.errors._error
        return
      }
      errorsRef.value = error.message
    },
  }

  return { ...baseService, ...extensions }
}

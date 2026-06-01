import qs from "qs"
import api from "../config/api"

export default {
  /**
   * @param {string} iri
   * @param {Object} [params]
   * @param {Object} [config] Extra axios config (e.g. { skipCourseContext: true }, signal, headers).
   * @returns {Promise<any>}
   */
  async get(iri, params = {}, config = {}) {
    const { data } = await api.get(iri, {
      params,
      ...config,
    })

    return data
  },

  /**
   * @param {string} endpoint
   * @param {Object} searchParams
   * @param {Object} [config] Extra axios config (e.g. { skipCourseContext: true }, signal, headers).
   * @returns {Promise<{totalItems: number, items: Object[], nextPageParams: {page: number, itemsPerPage: number}|null}>}
   */
  async getCollection(endpoint, searchParams = {}, config = {}) {
    const { data } = await api.get(endpoint, {
      params: searchParams,
      ...config,
    })

    let nextPageParams = null

    if (data["hydra:view"] && data["hydra:view"]["hydra:next"]) {
      const queryString = data["hydra:view"]["hydra:next"].split("?")[1] || ""

      nextPageParams = qs.parse(queryString)
    }

    return {
      totalItems: data["hydra:totalItems"] || data.totalItems,
      items: data["hydra:member"],
      nextPageParams,
    }
  },

  /**
   * Axios sets the Content-Type automatically from the payload type
   * (application/json for plain objects, multipart for FormData,
   * application/x-www-form-urlencoded for URLSearchParams), so it is not set here.
   * @param {string} endpoint
   * @param {Object} [params={}]
   * @param {Object} [additionalHeaders={}]
   * @param {Object} [options={}]
   * @returns {Promise<Object>}
   */
  async post(endpoint, params = {}, additionalHeaders = {}, options = {}) {
    const { data } = await api.post(endpoint, params, {
      headers: additionalHeaders,
      ...options,
    })

    return data
  },

  /**
   * @param {string} iri
   * @param {Object} params
   * @param {Object} [options={}]
   * @returns {Promise<Object>}
   */
  async put(iri, params, options = {}) {
    const { data } = await api.put(iri, params, options)

    return data
  },

  /**
   * Sends a PATCH request using the JSON merge-patch content type expected by API Platform.
   * @param {string} iri
   * @param {Object} params
   * @param {Object} [options={}]
   * @returns {Promise<Object>}
   */
  async patch(iri, params, options = {}) {
    const { headers = {}, ...rest } = options

    const { data } = await api.patch(iri, params, {
      ...rest,
      headers: { "Content-Type": "application/merge-patch+json", ...headers },
    })

    return data
  },

  /**
   * @param {string} endpoint
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async postForm(endpoint, params) {
    const { data } = await api.postForm(endpoint, params)

    return data
  },

  /**
   * @param {string} iri
   * @param {Object} [options={}]
   * @returns {Promise<any>}
   */
  async delete(iri, options = {}) {
    const { data } = await api.delete(iri, options)

    return data
  },

  /**
   * Sends a GET request and returns the full response (data, headers, status).
   * Use for binary downloads or when response headers are needed.
   * @param {string} iri
   * @param {Object} [options={}]
   * @returns {Promise<import("axios").AxiosResponse>}
   */
  async getRaw(iri, options = {}) {
    return await api.get(iri, options)
  },

  /**
   * Sends a POST request and returns the full response (data, headers, status).
   * Use for binary downloads or when response headers are needed.
   * @param {string} endpoint
   * @param {Object} [params={}]
   * @param {Object} [options={}]
   * @returns {Promise<import("axios").AxiosResponse>}
   */
  async postRaw(endpoint, params = {}, options = {}) {
    return await api.post(endpoint, params, options)
  },
}

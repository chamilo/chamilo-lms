import api from "../config/api"

export default {
  /**
   * @param {string} iri
   * @param {Object} [params]
   * @returns {Promise<any>}
   */
  async get(iri, params = {}) {
    const { data } = await api.get(iri, {
      params,
    })

    return data
  },

  /**
   * @param {string} endpoint
   * @param {Object} searchParams
   * @returns {Promise<{totalItems, items}>}
   */
  async getCollection(endpoint, searchParams = {}) {
    const { data } = await api.get(endpoint, {
      params: searchParams,
    })

    return {
      totalItems: data.totalItems,
      items: data["hydra:member"],
    }
  },

  /**
   * @param {string} endpoint
   * @param {Object} [params={}]
   * @param {boolean} [addContentType=false]
   * @returns {Promise<Object>}
   */
  async post(endpoint, params = {}, addContentType = false) {
    const config = addContentType
      ? {
        headers: {
          'Content-Type': 'application/json',
        },
      }
      : {}

    const { data } = await api.post(endpoint, params, config)

    return data
  },

  /**
   * @param {string} iri
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async put(iri, params) {
    const { data } = await api.put(iri, params)

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
   * @returns {Promise<void>}
   */
  async delete(iri) {
    await api.delete(iri)
  },
}

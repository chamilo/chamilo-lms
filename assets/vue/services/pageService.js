import api from "../config/api"

export default {
  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async post(params) {
    const { data } = await api.post("/api/pages", params)

    return data
  },

  /**
   * @param {string} iri
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async update(iri, params) {
    const { data } = await api.put(iri, params)

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

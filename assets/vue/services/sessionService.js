import api from "../config/api"

export default {
  /**
   * @param {string} iri
   * @returns {Promise<Object>}
   */
  async find(iri) {
    const { data } = await api.get(iri)

    return data
  },
}

import api from "../config/api"
import baseService from "./baseService"

/**
 * @param {string} userIri
 * @param {string} listType
 * @returns {Promise<{totalItems, items}>}
 */
async function findUserSubscriptions(userIri, listType) {
  return baseService.getCollection(`${userIri}/session_subscriptions/${listType}`)
}

export default {
  /**
   * @param {string} iri
   * @returns {Promise<Object>}
   */
  async find(iri) {
    const { data } = await api.get(iri)

    return data
  },

  findUserSubscriptions,
}

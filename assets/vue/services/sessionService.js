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
   * @param useBasic
   * @returns {Promise<Object>}
   */
  async find(iri, useBasic = false) {
    const endpoint = iri
    const groups = useBasic ? ["session:basic"] : ["session:read"]
    const { data } = await api.get(endpoint, {
      params: {
        "groups[]": groups,
      },
    })

    return data
  },

  findUserSubscriptions,

  /**
   * Get sequence requirements for a session
   *
   * @param {number} sessionId
   * @returns {Promise<Object>}
   */
  async getNextSession(sessionId) {
    const { data } = await api.get(`/sessions/${sessionId}/next-session`)
    return data
  },
}

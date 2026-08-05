import baseService from "./baseService"

export default {
  /**
   * Finds a push subscription by endpoint for a given user.
   * @param {string} endpoint
   * @param {number|string} userId
   * @returns {Promise<{totalItems, items}>}
   */
  findByEndpoint(endpoint, userId) {
    return baseService.getCollection("/api/push_subscriptions", {
      endpoint,
      "user.id": userId,
    })
  },

  /**
   * Creates a push subscription.
   * @param {Object} payload
   * @returns {Promise<Object>}
   */
  create(payload) {
    return baseService.post("/api/push_subscriptions", payload)
  },

  /**
   * Deletes a push subscription.
   * @param {number|string} id
   * @returns {Promise<any>}
   */
  remove(id) {
    return baseService.delete(`/api/push_subscriptions/${id}`)
  },
}

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

async function createWithCoursesAndUsers(payload) {
  return await baseService.post("/api/advanced/create-session-with-courses-and-users", payload)
}

async function sendCourseNotification(sessionId, studentId) {
  const payload = new FormData()
  payload.append('studentId', studentId)

  return await api.post(`/sessions/${sessionId}/send-course-notification`, payload)
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
  createWithCoursesAndUsers,
  sendCourseNotification,
}

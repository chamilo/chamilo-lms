import api from "../config/api"
import baseService from "./baseService"

/**
 * @param {string} userIri
 * @param {string} listType
 * @param {params}
 * @returns {Promise<{totalItems: number, items: Object[], nextPageParams: ({page: number, itemsPerPage: number}|null)}>}
 */
async function findUserSubscriptions(userIri, listType, params = {}) {
  return baseService.getCollection(`${userIri}/session_subscriptions/${listType}`, params)
}

async function createWithCoursesAndUsers(payload) {
  return await baseService.post("/api/advanced/create-session-with-courses-and-users", payload)
}

async function sendCourseNotification(sessionId, studentId) {
  const payload = new FormData()
  payload.append("studentId", studentId)

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

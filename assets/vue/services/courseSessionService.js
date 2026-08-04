import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

export default {
  async getList(params = {}) {
    return await baseService.get("/api/course-sessions/list", cleanParams(params))
  },

  async getOverview(sessionId) {
    return await baseService.get(`/api/course-sessions/${sessionId}/overview`)
  },

  async getUsers(sessionId, params = {}) {
    return await baseService.get(`/api/course-sessions/${sessionId}/users`, cleanParams(params))
  },

  async subscribeUsers(sessionId, userIds, csrfToken) {
    return await baseService.post("/api/course-sessions/actions/subscribe-users", {
      sessionId,
      userIds,
      csrfToken,
    })
  },

  async unsubscribeUsers(sessionId, userIds, csrfToken) {
    return await baseService.post("/api/course-sessions/actions/unsubscribe-users", {
      sessionId,
      userIds,
      csrfToken,
    })
  },

  async addUserToUrl(sessionId, userId, csrfToken) {
    return await baseService.post("/api/course-sessions/actions/add-user-to-url", {
      sessionId,
      userId,
      csrfToken,
    })
  },

  async getUserCourses(sessionId, userId) {
    return await baseService.get(`/api/course-sessions/${sessionId}/users/${userId}/courses`)
  },

  async updateUserCourses(sessionId, userId, avoidedCourseIds, csrfToken) {
    return await baseService.post("/api/course-sessions/actions/update-user-courses", {
      sessionId,
      userId,
      avoidedCourseIds,
      csrfToken,
    })
  },
}

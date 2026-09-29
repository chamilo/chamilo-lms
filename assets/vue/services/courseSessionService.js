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

  async subscribeUsers(sessionId, userIds) {
    return await baseService.post("/api/course-sessions/actions/subscribe-users", {
      sessionId,
      userIds,
    })
  },

  async unsubscribeUsers(sessionId, userIds) {
    return await baseService.post("/api/course-sessions/actions/unsubscribe-users", {
      sessionId,
      userIds,
    })
  },

  async addUserToUrl(sessionId, userId) {
    return await baseService.post("/api/course-sessions/actions/add-user-to-url", {
      sessionId,
      userId,
    })
  },

  async getUserCourses(sessionId, userId) {
    return await baseService.get(`/api/course-sessions/${sessionId}/users/${userId}/courses`)
  },

  async updateUserCourses(sessionId, userId, avoidedCourseIds) {
    return await baseService.post("/api/course-sessions/actions/update-user-courses", {
      sessionId,
      userId,
      avoidedCourseIds,
    })
  },
}

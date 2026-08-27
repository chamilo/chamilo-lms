import baseService from "./baseService"

function contextParams(sessionId = 0) {
  return { sid: Number(sessionId || 0) }
}

export default {
  async getConfiguration(courseId, userId, sessionId = 0) {
    return await baseService.get(
      `/ai/course/${Number(courseId)}/student-success/student/${Number(userId)}/configuration`,
      contextParams(sessionId),
    )
  },

  async prepareCourse(courseId, userId, sessionId = 0, provider = "") {
    return await baseService.post(
      `/ai/course/${Number(courseId)}/student-success/student/${Number(userId)}/prepare-course`,
      { provider },
      {},
      { params: contextParams(sessionId) },
    )
  },

  async analyze(courseId, userId, sessionId = 0, provider = "", teacherPrompt = "") {
    return await baseService.post(
      `/ai/course/${Number(courseId)}/student-success/student/${Number(userId)}/analyze`,
      {
        provider,
        teacherPrompt,
      },
      {},
      { params: contextParams(sessionId) },
    )
  },
}

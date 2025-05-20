import baseService from "./baseService"

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(searchParams) {
  return await baseService.getCollection("/api/course_rel_users", searchParams)
}

/**
 * Subscribes a user to a course.
 * @param {Object} params
 * @param {number} params.userId
 * @param {number} params.courseId
 * @returns {Promise<Object>}
 */
async function subscribe({ userId, courseId }) {
  return await baseService.post("/api/course_rel_users", {
    user: `/api/users/${userId}`,
    course: `/api/courses/${courseId}`,
    relationType: 0,
    status: 5,
  })
}

async function autoSubscribeCourse(courseId) {
  return await baseService.post(`/catalogue/auto-subscribe-course/${courseId}`)
}

export default {
  findAll,
  subscribe,
  autoSubscribeCourse,
}

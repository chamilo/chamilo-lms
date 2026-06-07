import baseService from "./baseService"

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(searchParams) {
  return await baseService.getCollection("/api/session_rel_course_rel_users", searchParams)
}

/**
 * Subscribes a user to a course within a session.
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
async function create(payload) {
  return await baseService.post("/api/session_rel_course_rel_users", payload)
}

export default {
  findAll,
  create,
}

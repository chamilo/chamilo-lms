import baseService from "./baseService"

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(searchParams) {
  return await baseService.getCollection("/api/session_rel_course_rel_users", searchParams)
}

export default {
  findAll,
}

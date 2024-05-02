import baseService from "./baseService"

/**
 * @param {string} userId
 * @returns {Promise<Object>}
 */
async function find(userId) {
  return await baseService.get(`/api/users/${userId}`)
}

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(searchParams) {
  return await baseService.getCollection("/api/users", searchParams)
}

/**
 * @param {string} username
 * @returns {Promise<{totalItems, items}>}
 */
async function findByUsername(username) {
  return await baseService.getCollection("/api/users", { username })
}

export default {
  find,
  findAll,
  findByUsername,
}

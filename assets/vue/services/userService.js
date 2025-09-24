import baseService from "./baseService"

/**
 * @param {string} userIri
 * @returns {Promise<Object>}
 */
async function find(userIri) {
  return await baseService.get(userIri)
}

/**
 * @param {number} userId
 * @returns {Promise<Object>}
 */
async function findById(userId) {
  return await baseService.get(`/api/users/${userId}`)
}

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(searchParams) {
  return await baseService.getCollection("/api/users", searchParams)
}

async function findUsersForSessionAdmin(searchParams) {
  return await baseService.get("/admin/sessionadmin/users", searchParams)
}

/**
 * @param {string} username
 * @returns {Promise<{totalItems, items}>}
 */
async function findByUsername(username) {
  return await baseService.getCollection("/api/users", { username })
}

/**
 * @param {string} term
 * @returns {Promise<{totalItems, items}>}
 */
async function findBySearchTerm(term) {
  return await baseService.getCollection("/api/users", { search: term })
}

/**
 * @param {number} accessUrlId
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
async function createOnAccessUrl(accessUrlId, payload) {
  return baseService.post(`/api/access_urls/${accessUrlId}/user`, payload)
}

export default {
  find,
  findById,
  findAll,
  findUsersForSessionAdmin,
  findByUsername,
  findBySearchTerm,
  createOnAccessUrl,
}

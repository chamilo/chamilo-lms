import baseService from "./baseService"

async function findAll(params) {
  return await baseService.getCollection("/api/session_rel_users", params)
}

/**
 * Subscribes a user to a session.
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
async function create(payload) {
  return await baseService.post("/api/session_rel_users", payload)
}

export default {
  findAll,
  create,
}

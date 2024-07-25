import makeService from "./api"
import baseService from "./baseService"

/**
 * @param {string} iri
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function update(iri, params) {
  return await baseService.put(iri, params)
}

export default makeService("message_rel_users", {
  update,
})

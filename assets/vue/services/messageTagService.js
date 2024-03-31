import baseService from "./baseService"

/**
 * @param {Object} params
 * @returns {Promise<{totalItems, items}>}
 */
async function findAll(params) {
  return await baseService.getCollection("/api/message_tags", params)
}

/**
 * @param {string} userIri
 * @param {string} searchTerm
 * @returns {Promise<{totalItems, items}>}
 */
async function searchUserTags(userIri, searchTerm) {
  return await findAll({
    user: userIri,
    tag: searchTerm,
  })
}

export default {
  findAll,
  searchUserTags,
}

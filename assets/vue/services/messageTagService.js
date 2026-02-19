import baseService from "./baseService"

/**
 * @param {Object} [params]
 * @returns {Promise<Object[]>}
 */
export async function findAll(params = {}) {
  const { items } = await baseService.getCollection("/api/message_tags", params)

  return items
}

/**
 * @param {string} searchTerm
 * @returns {Promise<{totalItems, items}>}
 */
export async function searchUserTags(searchTerm) {
  const { items } = await findAll({
    tag: searchTerm,
    pagination: false,
  })

  return items
}

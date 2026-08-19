import baseService from "./baseService"

/**
 *
 * @param {string} userIri
 * @returns {Promise<Object[]>}
 */
export async function findUserActivePortals(userIri) {
  const { items } = await baseService.getCollection(`${userIri}/access_urls`)

  return items
}

export async function findAll() {
  const { items } = await baseService.getCollection("/api/access_urls", {
    pagination: false,
    "order[url]": "asc",
  })

  return items
}

/**
 * @param {number} id
 * @returns {Promise<Object>}
 */
export async function findById(id) {
  return await baseService.get(`/api/access_urls/${id}`)
}

/**
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function deleteById(id) {
  await baseService.delete(`/api/access_urls/${id}`)
}

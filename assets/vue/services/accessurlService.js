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

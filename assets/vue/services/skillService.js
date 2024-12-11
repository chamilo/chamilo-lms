import baseService from "./baseService"

/**
 * @returns {Promise<Array>}
 */
export async function getSkillTree() {
  const { items } = await baseService.getCollection("/api/skills/tree")

  return items
}

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
export async function findAll(searchParams) {
  return await baseService.getCollection("api/skills", searchParams)
}

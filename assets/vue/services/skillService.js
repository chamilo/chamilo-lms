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

/**
 * @param {number} skillId
 * @returns {Promise<Object>}
 */
export async function getSkillDetail(skillId) {
  return await baseService.get(`/skill/${skillId}/detail-data`)
}

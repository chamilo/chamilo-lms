import baseService from "./baseService"

/**
 * @returns {Promise<Array>}
 */
export async function getSkillTree() {
  const data = await baseService.get("/api/skills/tree")

  if (Array.isArray(data)) {
    return data
  }

  return data?.["hydra:member"] || data?.member || []
}

/**
 * @param {Object} searchParams
 * @returns {Promise<{totalItems, items}>}
 */
export async function findAll(searchParams) {
  return await baseService.getCollection("/api/skills", searchParams)
}

/**
 * @param {number} skillId
 * @returns {Promise<Object>}
 */
export async function getSkillDetail(skillId) {
  return await baseService.get(`/skill/${skillId}/detail-data`)
}

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

/**
 * @param {number} userId
 * @param {number|null} skillId
 * @returns {Promise<Object>}
 */
export async function getAssignmentContext(userId, skillId = null) {
  const params = skillId ? { skillId } : {}
  return await baseService.get(`/api/skill-assignment/${userId}`, params)
}

/**
 * @param {number} userId
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
export async function updateAssignment(userId, payload) {
  return await baseService.post(`/api/skill-assignment/${userId}`, payload)
}

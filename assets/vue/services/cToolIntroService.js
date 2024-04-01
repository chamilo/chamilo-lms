import baseService from "./baseService"

/**
 * @param {number} cId
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function findCourseHomeInro(cId, params) {
  return await baseService.get(`/course/${cId}/getToolIntro`, params)
}

/**
 * @param {number} cId
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function addToolIntro(cId, params) {
  return baseService.post(`/course/${cId}/addToolIntro`, params)
}

/**
 * @param {number} toolIntroId
 * @returns {Promise<Object>}
 */
async function findById(toolIntroId) {
  return await baseService.get(`/api/c_tool_intros/${toolIntroId}`)
}

export default {
  findCourseHomeInro,
  addToolIntro,
  findById,
}

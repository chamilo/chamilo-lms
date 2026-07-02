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
 * Ensures a tool introduction exists for the given course tool (get-or-create).
 *
 * Posts to the CToolIntro API resource; the server resolves/creates the course
 * tool from `toolName` within the current course/session context (cid/sid/gid are
 * added automatically by the axios interceptor) and returns the existing
 * introduction untouched when one already exists. Returns the raw JSON-LD resource.
 *
 * @param {number} cId
 * @param {{ toolName: string, introText?: string }} params
 * @returns {Promise<Object>}
 */
async function addToolIntro(cId, { toolName, introText }) {
  return baseService.post("/api/c_tool_intros", {
    toolName,
    introText: introText ?? "",
  })
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

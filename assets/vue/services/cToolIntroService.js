import baseService from "./baseService"

/**
 * Resolves the active tool introduction for the current course/session context.
 *
 * Returns the session-specific intro when it exists, otherwise the base intro
 * flagged with `createInSession`, or an empty placeholder when none exists.
 * cid/sid/gid are added automatically by the axios interceptor.
 *
 * @param {string} [tool="course_homepage"]
 * @returns {Promise<Object>}
 */
async function findCourseHomeInro(tool = "course_homepage") {
  return await baseService.get("/api/c_tool_intros/current", { tool })
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

/**
 * Fetches a tool introduction by its IRI (e.g. "/api/c_tool_intros/8").
 *
 * @param {string} iri
 * @returns {Promise<Object>}
 */
async function findByIri(iri) {
  return await baseService.get(iri)
}

/**
 * Updates a tool introduction (PUT). The target IRI is taken from the item's @id.
 *
 * @param {Object} item
 * @returns {Promise<Object>}
 */
async function update(item) {
  return baseService.put(item["@id"], item)
}

export default {
  findCourseHomeInro,
  addToolIntro,
  findById,
  findByIri,
  update,
}

import api from "../config/api"

/**
 * Sets or toggles the current user's student view.
 *
 * This is the only endpoint allowed to carry isStudentView: changing the session
 * state is its documented purpose, and it checks the role before writing. Pass
 * isStudentView to set an explicit state; omit it to let the backend flip.
 *
 * The course context (cid/sid) is sent explicitly because the axios interceptor
 * only injects it into /api/ URLs, and the backend needs it to resolve the
 * contextual ROLE_CURRENT_COURSE_* roles that authorize the change.
 * @param {Object} [context]
 * @param {number|string|null} [context.cid] - Current course id
 * @param {number|string|null} [context.sid] - Current session id
 * @param {boolean} [context.isStudentView] - Target state; omit to toggle
 * @returns {Promise<string>} Either "studentview" or "teacherview"
 */
async function toogleStudentView({ cid, sid, isStudentView } = {}) {
  const params = {}

  if (cid) {
    params.cid = cid
  }

  if (sid) {
    params.sid = sid
  }

  if (undefined !== isStudentView) {
    params.isStudentView = isStudentView ? "true" : "false"
  }

  const { data } = await api.get("/toggle_student_view", { params })

  return data
}

export default {
  toogleStudentView,
}

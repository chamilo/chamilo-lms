import api from "../config/api"

/**
 * Toggles the current user's student view. The course context (cid/sid) is sent
 * explicitly so the backend can resolve the contextual ROLE_CURRENT_COURSE_* roles
 * used to authorize the toggle.
 * @param {Object} [context]
 * @param {number|string|null} [context.cid] - Current course id
 * @param {number|string|null} [context.sid] - Current session id
 * @returns {Promise<string>}
 */
async function toogleStudentView({ cid, sid } = {}) {
  const params = {}

  if (cid) {
    params.cid = cid
  }

  if (sid) {
    params.sid = sid
  }

  const { data } = await api.get("/toggle_student_view", { params })

  return data
}

export default {
  toogleStudentView,
}

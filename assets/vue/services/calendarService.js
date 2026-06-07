import baseService from "./baseService"

export default {
  /**
   * Fetches the sessions/events schedule for the current teacher's students.
   * @param {Object} [params={}] - Optional filters (sid, start, end).
   * @returns {Promise<Object[]>}
   */
  getMyStudentsSchedule(params = {}) {
    return baseService.get("/api/calendar/my-students-schedule", params)
  },

  /**
   * Fetches the sessions plan for a given year.
   * @param {number|string} year
   * @returns {Promise<Object>}
   */
  getSessionsPlan(year) {
    return baseService.get("/api/calendar/sessions-plan", { year })
  },
}

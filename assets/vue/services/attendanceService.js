import baseService from "./baseService"

export const ATTENDANCE_STATES = {
  ABSENT: { id: 0, label: "Absent", score: 0 },
  PRESENT: { id: 1, label: "Present", score: 1 },
  LATE_15: { id: 2, label: "Late < 15 min", score: 1 },
  LATE_15_PLUS: { id: 3, label: "Late > 15 min", score: 0.5 },
  ABSENT_JUSTIFIED: { id: 4, label: "Absent, justified", score: 0.25 },
}

export default {
  /**
   * Fetches all attendance lists for a specific course.
   * @param {Object} params - Filters for the attendance lists.
   * @returns {Promise<Object>} - Data of the attendance lists.
   */
  getAttendances: async (params) => {
    return await baseService.get(`/api/attendances/`, params)
  },

  /**
   * Fetches a specific attendance list by its ID.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Data of the specific attendance list.
   */
  getAttendance: async (attendanceId) => {
    return await baseService.get(`/api/attendances/${attendanceId}/`)
  },

  /**
   * Fetches groups filtered by the parent node (course).
   * @param {Number|String} parentNodeId - The ID of the parent resource node (course).
   * @returns {Promise<Array>} - List of groups associated with the course.
   */
  fetchGroups: async (parentNodeId) => {
    try {
      const { items } = await baseService.getCollection(`/api/groups`, { "resourceNode.parent": parentNodeId })

      return items.map((group) => ({
        label: group.title,
        value: group.iid,
      }))
    } catch (error) {
      console.error("Error fetching groups:", error)
      throw error
    }
  },

  /**
   * Creates a new attendance list. The course context is mirrored from the
   * request body to the query string so CidReqListener can resolve it and
   * CourseContextRoleListener can publish the contextual TEACHER role that
   * the operation requires.
   *
   * @param {Object} data - Data for the new attendance list (includes cid, sid).
   * @returns {Promise<Object>} - Data of the created attendance list.
   */
  createAttendance: async (data) => {
    return await baseService.post(`/api/attendances`, data)
  },

  /**
   * Updates an existing attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Object} data - Updated data for the attendance list.
   * @returns {Promise<Object>} - Data of the updated attendance list.
   */
  updateAttendance: async (attendanceId, data) => {
    return await baseService.put(`/api/attendances/${attendanceId}`, data)
  },

  /**
   * Deletes an attendance list. The course context (cid, optional sid/gid)
   * must be supplied as query parameters so CidReqListener can resolve the
   * course and CourseContextRoleListener can publish the contextual TEACHER
   * role that the operation requires.
   *
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {{cid: Number|String, sid?: Number|String, gid?: Number|String}} context
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteAttendance: async (attendanceId) => {
    return await baseService.delete(`/api/attendances/${attendanceId}`)
  },

  /**
   * Toggles the visibility of an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<void>} - Result of the toggle action.
   */
  toggleVisibility: async (attendanceId) => {
    await baseService.put(`/api/attendances/${attendanceId}/toggle_visibility`, {})
  },

  /**
   * Soft deletes an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<void>} - Result of the soft delete action.
   */
  softDelete: async (attendanceId) => {
    await baseService.put(`/api/attendances/${attendanceId}/soft_delete`, {})
  },

  /**
   * Adds a new calendar event to an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Object} data - Data for the new calendar event.
   * @returns {Promise<Object>} - Data of the created calendar event.
   */
  addCalendarEvent: async (attendanceId, data) => {
    return await baseService.post(`/api/attendances/${attendanceId}/calendars`, data)
  },

  /**
   * Updates an existing calendar event.
   * @param {Number|String} calendarId - ID of the calendar event.
   * @param {Object} data - Updated data for the calendar event.
   * @returns {Promise<Object>} - Data of the updated calendar event.
   */
  updateCalendarEvent: async (calendarId, data) => {
    return await baseService.put(`/api/c_attendance_calendars/${calendarId}`, data)
  },

  /**
   * Deletes a specific calendar event.
   * @param {Number|String} calendarId - ID of the calendar event.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteCalendarEvent: async (calendarId) => {
    return await baseService.delete(`/api/c_attendance_calendars/${calendarId}`)
  },

  /**
   * Adds a new calendar entry directly to an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Object} data - Calendar data, including repetition and groups.
   * @returns {Promise<Object>} - Data of the created calendar entry.
   */
  addAttendanceCalendar: async (attendanceId, data) => {
    return await baseService.post(`/api/attendances/${attendanceId}/calendars`, data)
  },

  /**
   * Fetches full attendance data (dates and attendance status).
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Full attendance data structured for Vue.
   */
  getFullAttendanceData: async (attendanceId) => {
    try {
      return await baseService.get(`/attendance/full-data`, { attendanceId })
    } catch (error) {
      console.error("Error fetching full attendance data:", error)
      throw error
    }
  },

  getAttendanceSheetUsers: async (attendanceId, params) => {
    try {
      return await baseService.get(`/attendance/${attendanceId}/users/context`, params)
    } catch (error) {
      console.error("Error fetching attendance sheet users:", error)
      throw error
    }
  },

  /**
   * Updates an existing calendar entry for an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Number|String} calendarId - ID of the calendar entry to update.
   * @param {Object} data - Updated calendar data.
   * @returns {Promise<Object>} - Data of the updated calendar entry.
   */
  updateAttendanceCalendar: async (attendanceId, calendarId, data) => {
    return await baseService.put(`/api/attendances/${attendanceId}/calendars/${calendarId}`, data)
  },

  /**
   * Deletes a specific calendar entry for an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Number|String} calendarId - ID of the calendar entry to delete.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteAttendanceCalendar: async (attendanceId, calendarId) => {
    return await baseService.delete(`/api/attendances/${attendanceId}/calendars/${calendarId}`)
  },

  /**
   * Deletes all calendar entries for a specific attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteAllAttendanceCalendars: async (attendanceId) => {
    return await baseService.delete(`/api/attendances/${attendanceId}/calendars`)
  },

  exportAttendanceToPdf: async (attendanceId, { cid, sid, gid }) => {
    const response = await baseService.getRaw(`/attendance/${attendanceId}/export/pdf`, {
      params: { cid, sid, gid },
      responseType: "blob",
    })

    return response.data
  },

  exportAttendanceToXls: async (attendanceId, { cid, sid, gid }) => {
    const response = await baseService.getRaw(`/attendance/${attendanceId}/export/xls`, {
      params: { cid, sid, gid },
      responseType: "blob",
    })

    return response.data
  },

  generateQrCode: async (attendanceId, { cid, sid, gid }) => {
    const response = await baseService.getRaw(`/attendance/${attendanceId}/qrcode`, {
      params: { cid, sid, gid },
      responseType: "blob",
    })

    return response.data
  },

  saveStudentOwnAttendance: async ({ courseId, entries }) => {
    return await baseService.post(`/attendance/validate-self`, { courseId, entries })
  },

  saveStudentSignature: async ({ calendarId, signature }) => {
    return await baseService.post(`/attendance/sign-self`, { calendarId, signature })
  },

  saveAttendanceSheet: async (data) => {
    try {
      return await baseService.post(`/attendance/sheet/save`, data)
    } catch (error) {
      console.error("Error saving attendance sheet:", error)
      throw error
    }
  },

  getAttendancesWithDoneCount: async (params) => {
    return await baseService.get(`/attendance/list_with_done_count`, params)
  },

  getStudentAttendanceData: async (attendanceId) => {
    try {
      return await baseService.get(`/attendance/${attendanceId}/student-dates`)
    } catch (error) {
      console.error("Error fetching student attendance data:", error)
      throw error
    }
  },

  getDateSheet: async (attendanceId, calendarId, { cid, sid, gid }) => {
    return await baseService.get(`/attendance/${attendanceId}/date/${calendarId}/sheet`, { cid, sid, gid })
  },
}

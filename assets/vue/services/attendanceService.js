import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

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
    const response = await axios.get(`${ENTRYPOINT}attendances/`, { params })
    return response.data
  },

  /**
   * Fetches a specific attendance list by its ID.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Data of the specific attendance list.
   */
  getAttendance: async (attendanceId) => {
    const response = await axios.get(`${ENTRYPOINT}attendances/${attendanceId}/`)
    return response.data
  },

  /**
   * Fetches groups filtered by the parent node (course).
   * @param {Number|String} parentNodeId - The ID of the parent resource node (course).
   * @returns {Promise<Array>} - List of groups associated with the course.
   */
  fetchGroups: async (parentNodeId) => {
    try {
      const response = await axios.get(`${ENTRYPOINT}groups`, {
        params: { "resourceNode.parent": parentNodeId },
      })
      return response.data["hydra:member"].map((group) => ({
        label: group.title,
        value: group.iid,
      }))
    } catch (error) {
      console.error("Error fetching groups:", error)
      throw error
    }
  },

  /**
   * Creates a new attendance list.
   * @param {Object} data - Data for the new attendance list.
   * @returns {Promise<Object>} - Data of the created attendance list.
   */
  createAttendance: async (data) => {
    const response = await axios.post(`${ENTRYPOINT}attendances`, data)
    return response.data
  },

  /**
   * Updates an existing attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Object} data - Updated data for the attendance list.
   * @returns {Promise<Object>} - Data of the updated attendance list.
   */
  updateAttendance: async (attendanceId, data) => {
    const response = await axios.put(`${ENTRYPOINT}attendances/${attendanceId}`, data)
    return response.data
  },

  /**
   * Deletes an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteAttendance: async (attendanceId) => {
    const response = await axios.delete(`${ENTRYPOINT}attendances/${attendanceId}`)
    return response.data
  },

  /**
   * Toggles the visibility of an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<void>} - Result of the toggle action.
   */
  toggleVisibility: async (attendanceId) => {
    const endpoint = `${ENTRYPOINT}attendances/${attendanceId}/toggle_visibility`
    await axios.put(endpoint, {}, { headers: { "Content-Type": "application/json" } })
  },

  /**
   * Soft deletes an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<void>} - Result of the soft delete action.
   */
  softDelete: async (attendanceId) => {
    const endpoint = `${ENTRYPOINT}attendances/${attendanceId}/soft_delete`
    await axios.put(endpoint, {}, { headers: { "Content-Type": "application/json" } })
  },

  /**
   * Adds a new calendar event to an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Object} data - Data for the new calendar event.
   * @returns {Promise<Object>} - Data of the created calendar event.
   */
  addCalendarEvent: async (attendanceId, data) => {
    const response = await axios.post(`${ENTRYPOINT}attendances/${attendanceId}/calendars`, data)
    return response.data
  },

  /**
   * Updates an existing calendar event.
   * @param {Number|String} calendarId - ID of the calendar event.
   * @param {Object} data - Updated data for the calendar event.
   * @returns {Promise<Object>} - Data of the updated calendar event.
   */
  updateCalendarEvent: async (calendarId, data) => {
    const response = await axios.put(`${ENTRYPOINT}c_attendance_calendars/${calendarId}`, data)
    return response.data
  },

  /**
   * Deletes a specific calendar event.
   * @param {Number|String} calendarId - ID of the calendar event.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteCalendarEvent: async (calendarId) => {
    const response = await axios.delete(`${ENTRYPOINT}c_attendance_calendars/${calendarId}`)
    return response.data
  },

  /**
   * Adds a new calendar entry directly to an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Object} data - Calendar data, including repetition and groups.
   * @returns {Promise<Object>} - Data of the created calendar entry.
   */
  addAttendanceCalendar: async (attendanceId, data) => {
    const endpoint = `${ENTRYPOINT}attendances/${attendanceId}/calendars`
    const response = await axios.post(endpoint, data, {
      headers: { "Content-Type": "application/json" },
    })
    return response.data
  },

  /**
   * Fetches full attendance data (dates and attendance status).
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Full attendance data structured for Vue.
   */
  getFullAttendanceData: async (attendanceId) => {
    try {
      const response = await axios.get(`/attendance/full-data`, {
        params: { attendanceId },
      })
      return response.data
    } catch (error) {
      console.error("Error fetching full attendance data:", error)
      throw error
    }
  },

  /**
   * Fetches users related to attendance based on course, session, or group.
   * @param {Object} params - Object with courseId, sessionId, and/or groupId.
   * @returns {Promise<Array>} - List of users.
   */
  getAttendanceSheetUsers: async (params) => {
    try {
      const response = await axios.get(`/attendance/users/context`, { params })
      return response.data
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
    const endpoint = `${ENTRYPOINT}attendances/${attendanceId}/calendars/${calendarId}`
    const response = await axios.put(endpoint, data)
    return response.data
  },

  /**
   * Deletes a specific calendar entry for an attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @param {Number|String} calendarId - ID of the calendar entry to delete.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteAttendanceCalendar: async (attendanceId, calendarId) => {
    const endpoint = `${ENTRYPOINT}attendances/${attendanceId}/calendars/${calendarId}`
    const response = await axios.delete(endpoint)
    return response.data
  },

  /**
   * Deletes all calendar entries for a specific attendance list.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Object>} - Result of the deletion.
   */
  deleteAllAttendanceCalendars: async (attendanceId) => {
    const endpoint = `${ENTRYPOINT}attendances/${attendanceId}/calendars`
    const response = await axios.delete(endpoint)
    return response.data
  },

  /**
   * Exports an attendance list to PDF format.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Blob>} - PDF file of the attendance list.
   */
  exportAttendanceToPdf: async (attendanceId) => {
    const response = await axios.get(`${ENTRYPOINT}attendances/${attendanceId}/export/pdf`, {
      responseType: "blob",
    })
    return response.data
  },

  /**
   * Exports an attendance list to XLS format.
   * @param {Number|String} attendanceId - ID of the attendance list.
   * @returns {Promise<Blob>} - XLS file of the attendance list.
   */
  exportAttendanceToXls: async (attendanceId) => {
    const response = await axios.get(`${ENTRYPOINT}attendances/${attendanceId}/export/xls`, {
      responseType: "blob",
    })
    return response.data
  },

  saveAttendanceSheet: async (data) => {
    try {
      const response = await axios.post(`/attendance/sheet/save`, data, {
        headers: { "Content-Type": "application/json" },
      })
      return response.data
    } catch (error) {
      console.error("Error saving attendance sheet:", error)
      throw error
    }
  },
}

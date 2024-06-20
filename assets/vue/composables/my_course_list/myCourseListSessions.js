import { SESSION_VISIBILITY_LIST_ONLY } from "../../constants/entity/session"
import { useSecurityStore } from "../../store/securityStore"

/**
 * @param {Object} session
 * @returns {{courses: Object[], isEnabled: boolean}}
 */
export function useSessionCard(session) {
  const securityStore = useSecurityStore()

  /**
   * @type {Object[]}
   */
  const courses = session.courses
    ? session.courses.map((sesionRelCourse) => ({ ...sesionRelCourse.course, _id: sesionRelCourse.course.id }))
    : []

  const isEnabled = session.accessVisibility !== SESSION_VISIBILITY_LIST_ONLY || securityStore.isAdmin

  return {
    courses,
    isEnabled,
  }
}

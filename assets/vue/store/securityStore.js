import { defineStore } from "pinia"
import { isEmpty } from "lodash"
import { computed, ref } from "vue"
import securityService from "../services/securityService"
import { usePlatformConfig } from "./platformConfig"

// Contextual ROLE_CURRENT_COURSE_* roles, mirroring User::CONTEXT_ROLES on the
// backend. These are recomputed per course/session/group context and must never
// leak across courses, so they are always replaced wholesale (see setContextRoles).
const CONTEXT_ROLES = [
  "ROLE_CURRENT_COURSE_TEACHER",
  "ROLE_CURRENT_COURSE_STUDENT",
  "ROLE_CURRENT_COURSE_SESSION_TEACHER",
  "ROLE_CURRENT_COURSE_SESSION_STUDENT",
  "ROLE_CURRENT_COURSE_GROUP_TEACHER",
  "ROLE_CURRENT_COURSE_GROUP_STUDENT",
]

export const useSecurityStore = defineStore("security", () => {
  const user = ref(null)
  const isLoading = ref(true)
  const isAuthenticated = computed(() => !isEmpty(user.value))

  const platformConfigStore = usePlatformConfig()

  function setUser(newUserInfo) {
    user.value = newUserInfo
  }

  const hasRole = computed(() => (role) => {
    if (user.value && Array.isArray(user.value.roles)) {
      return user.value.roles.indexOf(role) !== -1
    }

    return false
  })

  /**
   * @param {string} role
   */
  const removeRole = (role) => {
    if (!user.value || !Array.isArray(user.value.roles)) return
    const index = user.value.roles.indexOf(role)

    if (index > -1) {
      user.value.roles.splice(index, 1)
    }
  }

  /**
   * Replaces all contextual ROLE_CURRENT_COURSE_* roles with the given set,
   * leaving personal/global roles untouched. Pass an empty array to clear the
   * course context (e.g. when leaving a course).
   * @param {string[]} roles
   */
  const setContextRoles = (roles) => {
    if (!user.value || !Array.isArray(user.value.roles)) return

    const personalRoles = user.value.roles.filter((role) => !CONTEXT_ROLES.includes(role))
    const contextRoles = (roles ?? []).filter((role) => CONTEXT_ROLES.includes(role))
    const nextRoles = [...personalRoles, ...contextRoles]

    // Skip the reactive reassignment when the resulting role set is unchanged.
    const isUnchanged =
      nextRoles.length === user.value.roles.length && nextRoles.every((role, index) => role === user.value.roles[index])

    if (isUnchanged) return

    user.value.roles = nextRoles
  }

  const isStudent = computed(() => hasRole.value("ROLE_STUDENT"))

  const isStudentBoss = computed(() => hasRole.value("ROLE_STUDENT_BOSS"))

  const isHRM = computed(() => hasRole.value("ROLE_HR"))

  const isAdmin = computed(() => hasRole.value("ROLE_ADMIN") || hasRole.value("ROLE_GLOBAL_ADMIN"))

  const isTeacher = computed(() => isAdmin.value || hasRole.value("ROLE_TEACHER"))

  // Contextual ROLE_CURRENT_COURSE_* roles. These mirror the backend
  // (CourseAccessResolver) for the current course/session/group and are kept in
  // sync per navigation by the router's beforeResolve guard.
  const isCurrentCourseStudent = computed(() => hasRole.value("ROLE_CURRENT_COURSE_STUDENT"))
  const isCurrentCourseTeacher = computed(() => hasRole.value("ROLE_CURRENT_COURSE_TEACHER"))
  const isCurrentCourseSessionStudent = computed(() => hasRole.value("ROLE_CURRENT_COURSE_SESSION_STUDENT"))
  const isCurrentCourseSessionTeacher = computed(() => hasRole.value("ROLE_CURRENT_COURSE_SESSION_TEACHER"))
  const isCurrentCourseGroupStudent = computed(() => hasRole.value("ROLE_CURRENT_COURSE_GROUP_STUDENT"))
  const isCurrentCourseGroupTeacher = computed(() => hasRole.value("ROLE_CURRENT_COURSE_GROUP_TEACHER"))

  /**
   * The backend grants a course teacher both STUDENT and TEACHER contextual
   * roles, so teacher presence wins (matches api_is_course_admin). Suppressed
   * while the student view is active.
   */
  const isCurrentTeacher = computed(() => {
    if (platformConfigStore.isStudentViewActive) return false
    if (isAdmin.value) return true

    return isCurrentCourseTeacher.value
  })

  // Mirrors api_is_course_admin() (public/main/inc/lib/api.lib.php):
  // platform admin OR session/course teacher of the current course.
  const isCourseAdmin = computed(() => {
    if (isAdmin.value) return true

    return isCurrentCourseSessionTeacher.value || isCurrentCourseTeacher.value
  })

  const isSessionAdmin = computed(() => hasRole.value("ROLE_SESSION_MANAGER"))

  async function checkSession() {
    // Only check user session when user info is stored
    if (!isAuthenticated.value) {
      isLoading.value = false
      return
    }

    isLoading.value = true
    try {
      const response = await securityService.checkSession()
      if (response.isAuthenticated) {
        user.value = response.user
      } else {
        user.value = null
      }
    } catch (error) {
      console.error("[SecurityStore] Failed to check session", error)
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  return {
    user,
    setUser,
    isLoading,
    isAuthenticated,
    hasRole,
    removeRole,
    setContextRoles,
    isStudent,
    isStudentBoss,
    isHRM,
    isTeacher,
    isAdmin,
    isCurrentCourseStudent,
    isCurrentCourseTeacher,
    isCurrentCourseSessionStudent,
    isCurrentCourseSessionTeacher,
    isCurrentCourseGroupStudent,
    isCurrentCourseGroupTeacher,
    isCurrentTeacher,
    isCourseAdmin,
    isSessionAdmin,
    checkSession,
  }
})

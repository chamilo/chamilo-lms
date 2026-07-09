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

// Symfony role hierarchy, mirroring security.role_hierarchy in
// config/packages/security.yaml — keep in sync with that file. Purely
// self-referential YAML entries (e.g. ROLE_ANONYMOUS: [ROLE_ANONYMOUS]) are
// omitted because a role always implies itself in the reachable set.
const ROLE_HIERARCHY = {
  ROLE_STUDENT: ["ROLE_USER"],
  ROLE_ADMIN: [
    "ROLE_USER",
    "ROLE_STUDENT",
    "ROLE_TEACHER",
    "ROLE_QUESTION_MANAGER",
    "ROLE_SESSION_MANAGER",
    "ROLE_CURRENT_COURSE_TEACHER",
    "ROLE_CURRENT_COURSE_SESSION_TEACHER",
    "ROLE_CURRENT_COURSE_GROUP_TEACHER",
    "ROLE_ALLOWED_TO_SWITCH",
  ],
  ROLE_GLOBAL_ADMIN: ["ROLE_ADMIN", "ROLE_ALLOWED_TO_SWITCH"],
  ROLE_TEACHER: ["ROLE_STUDENT"],
  ROLE_HR: ["ROLE_TEACHER", "ROLE_ALLOWED_TO_SWITCH"],
  ROLE_QUESTION_MANAGER: ["ROLE_STUDENT"],
  ROLE_SESSION_MANAGER: ["ROLE_STUDENT", "ROLE_ALLOWED_TO_SWITCH"],
  ROLE_STUDENT_BOSS: ["ROLE_STUDENT"],
  ROLE_INVITEE: ["ROLE_STUDENT"],
  ROLE_CURRENT_COURSE_TEACHER: ["ROLE_CURRENT_COURSE_STUDENT"],
  ROLE_CURRENT_COURSE_GROUP_TEACHER: ["ROLE_CURRENT_COURSE_GROUP_STUDENT"],
  ROLE_CURRENT_COURSE_SESSION_TEACHER: ["ROLE_CURRENT_COURSE_SESSION_STUDENT"],
}

/**
 * Computes the transitive set of roles reachable from the given roles by
 * following ROLE_HIERARCHY, mirroring Symfony's RoleHierarchy resolution.
 * @param {string[]} roles
 * @returns {Set<string>}
 */
function getReachableRoles(roles) {
  const reachableRoles = new Set()
  const pending = [...roles]

  while (pending.length > 0) {
    const role = pending.pop()

    if (reachableRoles.has(role)) {
      continue
    }

    reachableRoles.add(role)

    const impliedRoles = ROLE_HIERARCHY[role]

    if (impliedRoles) {
      pending.push(...impliedRoles)
    }
  }

  return reachableRoles
}

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

  // Roles reachable from the user's assigned roles through the Symfony hierarchy.
  const reachableRoles = computed(() => {
    if (user.value && Array.isArray(user.value.roles)) {
      return getReachableRoles(user.value.roles)
    }

    return new Set()
  })

  /**
   * Hierarchy-aware role check, equivalent to Symfony's is_granted(): unlike
   * hasRole (literal membership), a user granted ROLE_ADMIN is also granted
   * ROLE_TEACHER, and ROLE_CURRENT_COURSE_TEACHER implies ROLE_CURRENT_COURSE_STUDENT.
   */
  const isGranted = computed(() => (role) => reachableRoles.value.has(role))

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
  // sync per navigation by the router's beforeResolve guard. They use isGranted
  // so the hierarchy applies (e.g. a session teacher is also a session student).
  const isCurrentCourseStudent = computed(() => isGranted.value("ROLE_CURRENT_COURSE_STUDENT"))
  const isCurrentCourseTeacher = computed(() => isGranted.value("ROLE_CURRENT_COURSE_TEACHER"))
  const isCurrentCourseSessionStudent = computed(() => isGranted.value("ROLE_CURRENT_COURSE_SESSION_STUDENT"))
  const isCurrentCourseSessionTeacher = computed(() => isGranted.value("ROLE_CURRENT_COURSE_SESSION_TEACHER"))
  const isCurrentCourseGroupStudent = computed(() => isGranted.value("ROLE_CURRENT_COURSE_GROUP_STUDENT"))
  const isCurrentCourseGroupTeacher = computed(() => isGranted.value("ROLE_CURRENT_COURSE_GROUP_TEACHER"))

  /**
   * Teacher of the current course context — base course OR session — mirroring
   * api_is_course_admin() (public/main/inc/lib/api.lib.php), but suppressed while
   * the student view is active. Equivalent to isCourseAdmin gated by the student view.
   */
  const isCurrentTeacher = computed(() => {
    if (platformConfigStore.isStudentViewActive) return false
    if (isAdmin.value) return true

    return isCurrentCourseTeacher.value || isCurrentCourseSessionTeacher.value
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
    isGranted,
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

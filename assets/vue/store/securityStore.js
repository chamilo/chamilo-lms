import { defineStore } from "pinia"
import { isEmpty } from "lodash"
import { computed, ref } from "vue"
import securityService from "../services/securityService"
import { usePlatformConfig } from "./platformConfig"

export const useSecurityStore = defineStore("security", () => {
  const user = ref(null)
  const isLoading = ref(true)
  const isAuthenticated = computed(() => !isEmpty(user.value))

  const platformConfigStore = usePlatformConfig()

  function setUser(newUserInfo) {
    user.value = newUserInfo
  }

  const hasRole = computed(() => (role) => {
    if (user.value && user.value.roles) {
      return user.value.roles.indexOf(role) !== -1
    }

    return false
  })

  /**
   * @param {string} role
   */
  const removeRole = (role) => {
    if (!user.value || !user.value.roles) return
    const index = user.value.roles.indexOf(role)

    if (index > -1) {
      user.value.roles.splice(index, 1)
    }
  }

  const isStudent = computed(() => hasRole.value("ROLE_STUDENT"))

  const isStudentBoss = computed(() => hasRole.value("ROLE_STUDENT_BOSS"))

  const isHRM = computed(() => hasRole.value("ROLE_HR"))

  const isAdmin = computed(() => hasRole.value("ROLE_ADMIN") || hasRole.value("ROLE_GLOBAL_ADMIN"))

  const isTeacher = computed(() => isAdmin.value || hasRole.value("ROLE_TEACHER"))

  const isCurrentTeacher = computed(
    () => (isAdmin.value || hasRole.value("ROLE_CURRENT_COURSE_TEACHER")) && !platformConfigStore.isStudentViewActive,
  )

  const isCourseAdmin = computed(
    () =>
      isAdmin.value ||
      hasRole.value("ROLE_CURRENT_COURSE_SESSION_TEACHER") ||
      hasRole.value("ROLE_CURRENT_COURSE_TEACHER"),
  )

  const isSessionAdmin = computed(() => hasRole.value("ROLE_SESSION_MANAGER"))

  async function checkSession() {
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
    isStudent,
    isStudentBoss,
    isHRM,
    isTeacher,
    isCurrentTeacher,
    isCourseAdmin,
    isSessionAdmin,
    isAdmin,
    checkSession,
  }
})

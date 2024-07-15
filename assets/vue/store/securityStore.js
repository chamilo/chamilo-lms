import { defineStore } from "pinia"
import { isEmpty } from "lodash"
import { computed, ref } from "vue"
import securityService from "../services/securityService"

export const useSecurityStore = defineStore("security", () => {
  const user = ref(null)
  const isLoading = ref(true)
  const isAuthenticated = computed(() => !isEmpty(user.value))

  const hasRole = computed(() => (role) => {
    if (user.value && user.value.roles) {
      return user.value.roles.indexOf(role) !== -1
    }

    return false
  })

  const isStudent = computed(() => hasRole.value("ROLE_STUDENT"))

  const isStudentBoss = computed(() => hasRole.value("ROLE_STUDENT_BOSS"))

  const isHRM = computed(() => hasRole.value("ROLE_HR"))

  const isTeacher = computed(() => (isAdmin.value ? true : hasRole.value("ROLE_TEACHER")))

  const isCurrentTeacher = computed(() => (isAdmin.value ? true : hasRole.value("ROLE_CURRENT_COURSE_TEACHER")))

  const isCourseAdmin = computed(() =>
    isAdmin.value
      ? true
      : hasRole.value("ROLE_CURRENT_COURSE_SESSION_TEACHER") || hasRole.value("ROLE_CURRENT_COURSE_TEACHER"),
  )

  const isSessionAdmin = computed(() => hasRole.value("ROLE_SESSION_MANAGER"))

  const isAdmin = computed(() => hasRole.value("ROLE_SUPER_ADMIN") || hasRole.value("ROLE_ADMIN"))


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
      console.error("Error checking session:", error)
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  return {
    user,
    isLoading,
    isAuthenticated,
    hasRole,
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

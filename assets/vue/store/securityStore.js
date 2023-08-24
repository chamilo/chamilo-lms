import { defineStore } from "pinia"
import { isEmpty } from "lodash"
import { computed, ref } from "vue"

export const useSecurityStore = defineStore("security", () => {
  const user = ref()

  const isAuthenticated = computed(() => !isEmpty(user.value))

  const hasRole = computed(() => (role) => {
    if (user.value && user.value.roles) {
      return user.value.roles.indexOf(role) !== -1
    }

    return false
  })

  const isStudent = computed(() => hasRole.value("ROLE_STUDENT"))

  const isStudentBoss = computed(() => hasRole.value("ROLE_STUDENT_BOSS"))

  const isHRM = computed(() => hasRole.value("ROLE_RRHH"))

  const isTeacher = computed(() => (isAdmin.value ? true : hasRole.value("ROLE_TEACHER")))

  const isCurrentTeacher = computed(() => (isAdmin.value ? true : hasRole.value("ROLE_CURRENT_COURSE_TEACHER")))

  const isCourseAdmin = computed(() =>
    isAdmin.value
      ? true
      : hasRole.value("ROLE_CURRENT_COURSE_SESSION_TEACHER") && hasRole.value("ROLE_CURRENT_COURSE_TEACHER"),
  )

  const isSessionAdmin = computed(() => hasRole.value("ROLE_SESSION_MANAGER"))

  const isAdmin = computed(() => hasRole.value("ROLE_SUPER_ADMIN") || hasRole.value("ROLE_ADMIN"))

  return {
    user,
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
  }
})

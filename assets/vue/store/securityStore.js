import { defineStore } from "pinia"
import { isEmpty } from "lodash"

export const useSecurityStore = defineStore("security", {
  state: () => ({
    user: null,
  }),

  getters: {
    isAuthenticated: (state) => !isEmpty(state.user),

    hasRole: (state) => (role) => {
      if (state.user && state.user.roles) {
        return state.user.roles.indexOf(role) !== -1
      }

      return false
    },

    isStudent() {
      return this.hasRole("ROLE_STUDENT")
    },

    isStudentBoss() {
      return this.hasRole("ROLE_STUDENT_BOSS")
    },

    isHRM() {
      return this.hasRole("ROLE_RRHH")
    },

    isTeacher() {
      if (this.isAdmin) {
        return true
      }

      return this.hasRole("ROLE_TEACHER")
    },

    isCurrentTeacher() {
      if (this.isAdmin) {
        return true
      }

      return this.hasRole("ROLE_CURRENT_COURSE_TEACHER")
    },

    isCourseAdmin() {
      if (this.isAdmin) {
        return true
      }

      return this.hasRole("ROLE_CURRENT_COURSE_SESSION_TEACHER") && this.hasRole("ROLE_CURRENT_COURSE_TEACHER")
    },

    isSessionAdmin() {
      return this.hasRole("ROLE_SESSION_MANAGER")
    },

    isAdmin() {
      return this.hasRole("ROLE_SUPER_ADMIN") || this.hasRole("ROLE_ADMIN")
    },
  },
})

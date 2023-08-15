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
      return this.hasRole('ROLE_STUDENT')
    },

    isStudentBoss() {
      return this.hasRole('ROLE_STUDENT_BOSS')
    },

    isAdmin() {
      return this.isAuthenticated && (this.hasRole("ROLE_SUPER_ADMIN") || this.hasRole("ROLE_ADMIN"))
    },
  },
})

export default {
  namespaced: true,
  state: {
    isLoading: false,
    error: null,
    isAuthenticated: false,
    user: null,
  },
  getters: {
    isLoading(state) {
      return state.isLoading
    },
    error(state) {
      return state.error
    },
    isAuthenticated(state) {
      return state.isAuthenticated
    },
    isAdmin(state, getters) {
      return getters.isAuthenticated && (getters.hasRole("ROLE_SUPER_ADMIN") || getters.hasRole("ROLE_ADMIN"))
    },
    isCourseAdmin(state, getters) {
      if (getters.isAdmin) {
        return true
      }

      return (
        getters.isAuthenticated &&
        getters.hasRole("ROLE_CURRENT_COURSE_SESSION_TEACHER") &&
        getters.hasRole("ROLE_CURRENT_COURSE_TEACHER")
      )
    },
    isCurrentTeacher(state, getters) {
      if (!getters.isAuthenticated) {
        return false
      }

      if (getters.hasRole("ROLE_SUPER_ADMIN") || getters.hasRole("ROLE_ADMIN")) {
        return true
      }

      return getters.hasRole("ROLE_CURRENT_COURSE_TEACHER")
    },
    isBoss(state, getters) {
      return getters.hasRole("ROLE_STUDENT_BOSS")
    },
    isStudent(state, getters) {
      return getters.hasRole("ROLE_STUDENT")
    },
    getUser(state) {
      return state.user
    },
    hasRole(state) {
      return (role) => {
        if (state.user.roles) {
          return state.user.roles.indexOf(role) !== -1
        }

        return false
      }
    },
  },
  actions: {},
}

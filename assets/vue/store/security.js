import SecurityAPI from "../api/security";

const AUTHENTICATING = "AUTHENTICATING",
    AUTHENTICATING_SUCCESS = "AUTHENTICATING_SUCCESS",
    AUTHENTICATING_ERROR = "AUTHENTICATING_ERROR",
    AUTHENTICATING_LOGOUT = "AUTHENTICATING_LOGOUT",
    PROVIDING_DATA_ON_REFRESH_SUCCESS = "PROVIDING_DATA_ON_REFRESH_SUCCESS";

export default {
    namespaced: true,
    state: {
        isLoading: false,
        error: null,
        isAuthenticated: false,
        user: null
    },
    getters: {
        isLoading(state) {
            return state.isLoading;
        },
        hasError(state) {
            return state.error !== null;
        },
        error(state) {
            return state.error;
        },
        isAuthenticated(state) {
            return state.isAuthenticated;
        },
        isAdmin(state, getters) {
            return getters.isAuthenticated && (getters.hasRole('ROLE_SUPER_ADMIN') || getters.hasRole('ROLE_ADMIN'));
        },
        isCourseAdmin(state, getters) {
          if (getters.isAdmin) {
            return true
          }

          return getters.isAuthenticated
            && getters.hasRole("ROLE_CURRENT_COURSE_SESSION_TEACHER")
            && getters.hasRole("ROLE_CURRENT_COURSE_TEACHER")
        },
        isCurrentTeacher(state, getters) {
            if (!getters.isAuthenticated) {
                return false;
            }

            if (getters.hasRole('ROLE_SUPER_ADMIN') || getters.hasRole('ROLE_ADMIN')) {
                return true
            }

            return getters.hasRole('ROLE_CURRENT_COURSE_TEACHER');
        },
        isBoss(state, getters) {
            return getters.hasRole('ROLE_STUDENT_BOSS');
        },
        isStudent(state, getters) {
            return getters.hasRole('ROLE_STUDENT');
        },
        getUser(state) {
            return state.user;
        },
        hasRole(state) {
            return role => {
                if (state.user.roles) {
                    return state.user.roles.indexOf(role) !== -1;
                }

                return false;
            };
        }
    },
    mutations: {
        [AUTHENTICATING](state) {
            state.isLoading = true;
            state.error = null;
            state.isAuthenticated = false;
            state.user = null;
        },
        [AUTHENTICATING_SUCCESS](state, user) {
            state.isLoading = true;
            state.error = null;
            state.isAuthenticated = true;
            state.user = user;
        },
        [AUTHENTICATING_ERROR](state, error) {
            state.isLoading = false;
            state.error = error;
            state.isAuthenticated = false;
            state.user = null;
        },
        [AUTHENTICATING_LOGOUT](state, error) {
            console.log('AUTHENTICATING_LOGOUT');
            state.isLoading = false;
            state.error = error;
            state.isAuthenticated = false;
            state.user = null;
        },
        [PROVIDING_DATA_ON_REFRESH_SUCCESS](state, payload) {
            state.isLoading = false;
            state.error = null;
            state.isAuthenticated = payload.isAuthenticated;
            state.user = payload.user;
        }
    },
    actions: {
        async login({commit}, payload) {
          commit(AUTHENTICATING);
          try {
            const response = await SecurityAPI.login(payload.login, payload.password);
            commit(AUTHENTICATING_SUCCESS, response.data);
            return response.data;
          } catch (error) {
            commit(AUTHENTICATING_ERROR, error);
            throw error;
          }
        },

        async logout({commit}) {
            console.log('logout store/security');
            await SecurityAPI.logout().then(response => {
                commit(AUTHENTICATING_LOGOUT);
                return response.data;
            }).catch(error => {
                commit(AUTHENTICATING_ERROR, error);
            });
        },
        onRefresh({commit}, payload) {
            commit(PROVIDING_DATA_ON_REFRESH_SUCCESS, payload);
        }
    }
}

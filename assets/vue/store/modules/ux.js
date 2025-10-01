export default {
  namespaced: true,
  state: () => ({
    forbiddenMessage: "",
  }),
  mutations: {
    SET_FORBIDDEN(state, msg) {
      state.forbiddenMessage = msg || ""
    },
    CLEAR_FORBIDDEN(state) {
      state.forbiddenMessage = ""
    },
  },
  actions: {
    showForbidden({ commit }, msg) {
      commit("SET_FORBIDDEN", msg)
    },
    clearForbidden({ commit }) {
      commit("CLEAR_FORBIDDEN")
    },
  },
  getters: {
    forbiddenMessage: (s) => s.forbiddenMessage,
  },
}

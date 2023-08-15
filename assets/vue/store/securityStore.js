import { defineStore } from "pinia"
import { isEmpty } from "lodash"

export const useSecurityStore = defineStore("security", {
  state: () => ({
    user: null,
  }),

  getters: {
    isAuthenticated: (state) => !isEmpty(state.user),
  },
})

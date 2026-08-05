import { defineStore } from "pinia"
import socialService from "../services/socialService"

export const useSocialStore = defineStore("social", {
  state: () => ({
    showFullProfile: false,
  }),

  actions: {
    async checkUserRelation(currentUserId, profileUserId) {
      try {
        const data = await socialService.getUserRelation(currentUserId, profileUserId)
        this.showFullProfile = data.isAllowed
      } catch (error) {
        console.error("Error checking user relation:", error)
        this.showFullProfile = false
      }
    },
  },

  getters: {
    isProfileVisible: (state) => state.showFullProfile,
  },
})

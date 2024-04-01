import { defineStore } from "pinia"
import axios from "axios"

export const useSocialStore = defineStore("social", {
  state: () => ({
    showFullProfile: false,
  }),

  actions: {
    async checkUserRelation(currentUserId, profileUserId) {
      try {
        const response = await axios.get(`/social-network/user-relation/${currentUserId}/${profileUserId}`)
        this.showFullProfile = response.data.isAllowed
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

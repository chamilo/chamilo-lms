import { defineStore } from "pinia"
import { useSecurityStore } from "./securityStore"
import messageRelUSerService from "../services/messagereluser"

export const useMessageRelUserStore = defineStore("messageRelUser", {
  state: () => ({
    countUnread: 0,
  }),
  actions: {
    async findUnreadCount() {
      const securityStore = useSecurityStore()

      const response = await messageRelUSerService.findAll({
        params: {
          read: false,
          receiver: securityStore.user["@id"],
          itemsPerPage: 1,
        },
      })
      const json = await response.json()

      this.countUnread = json["hydra:totalItems"]
    },
  },
})

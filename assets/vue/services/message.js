import makeService from "./api"
import baseService from "./baseService"

// MIGRATION IN PROGRESS. makeService is deprecated
// if you use some method in this service you should try to refactor it with new baseService defining async functions
// like create below. A fully migrated service looks like: assets/vue/services/userService.js.
// BE AWARE that makeService use vuex, so we need to ensure behaviour to be the same as the older service
// When makeService is fully migrated, export by default the const messageService and change imports in all components
// that use this service
export default makeService("messages")

/**
 * @param {Object} message
 * @returns {Promise<Object>}
 */
async function create(message) {
  return await baseService.post("/api/messages", message)
}

async function countUnreadMessages(params) {
  params["exists[receivers.deletedAt]"] = false
  const queryParams = new URLSearchParams(params).toString()
  return await baseService.get(`/api/messages?${queryParams}`)
}

export const messageService = {
  create,
  countUnreadMessages,
}

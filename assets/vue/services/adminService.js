import baseService from "./baseService"

export default {
  registerCampus: async (doNotListCampus) => {
    await baseService.post("/admin/register-campus", {
      donotlistcampus: doNotListCampus,
    })
  },

  findAnnouncements: () => baseService.get("/main/inc/ajax/admin.ajax.php?a=get_latest_news"),
  findVersion: () => baseService.get("/main/inc/ajax/admin.ajax.php?a=version"),
  findSupport: () => baseService.get("/main/inc/ajax/admin.ajax.php?a=get_support"),
  findBlocks: () => baseService.get("/admin/index"),

  fetchThirdParties: async () => {
    const data = await baseService.get("/api/third_parties")
    return data["hydra:member"] || []
  },

  createThirdParty: async (payload) => {
    return await baseService.post("/api/third_parties", payload)
  },

  fetchExchanges: async (thirdPartyId = null) => {
    const query = thirdPartyId
      ? `?thirdParty=${encodeURIComponent(`/api/third_parties/${thirdPartyId}`)}`
      : ""
    const data = await baseService.get(`/api/third_party_data_exchanges${query}`)
    return data["hydra:member"] || []
  },

  fetchExchangeUsers: async () => {
    const data = await baseService.get("/api/third_party_data_exchange_users?pagination=false")
    return data["hydra:member"] || []
  },

  fetchUsers: async () => {
    const data = await baseService.get("/api/users?pagination=false")
    return data["hydra:member"] || []
  },

  createExchange: (payload) => baseService.post("/api/third_party_data_exchanges", payload),

  assignExchangeUsers: (userPayload) =>
    Promise.all(userPayload.map((p) => baseService.post("/api/third_party_data_exchange_users", p))),
}

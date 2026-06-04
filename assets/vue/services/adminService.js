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

  findSystemUpdateStatus: () => baseService.get("/admin/system-update/status"),

  checkSystemUpdateManifest: (payload) => baseService.post("/admin/system-update/check", payload),

  verifySystemUpdatePackage: (payload) => baseService.post("/admin/system-update/verify", payload),

  runSystemUpdatePreflight: (payload) => baseService.post("/admin/system-update/preflight", payload),

  stageSystemUpdatePackage: (payload) => baseService.post("/admin/system-update/stage", payload),

  buildSystemUpdateApplyPlan: (payload) => baseService.post("/admin/system-update/apply-plan", payload),

  applySystemUpdateFiles: (payload) => baseService.post("/admin/system-update/apply-files", payload),

  runSystemUpdatePostApplyChecks: (payload) => baseService.post("/admin/system-update/post-apply", payload),

  runSystemUpdateMigrationSafetyChecks: (payload) => baseService.post("/admin/system-update/migration-safety", payload),

  runSystemUpdatePostApplyActions: (payload) => baseService.post("/admin/system-update/run-post-apply", payload),

  findSystemUpdateProgress: (operationId) =>
    baseService.get(`/admin/system-update/progress/${encodeURIComponent(operationId)}`),

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

  updateThirdParty: (id, payload) =>
    baseService.put(`/api/third_parties/${id}`, payload),

  deleteThirdParty: (idOrIri) =>
    baseService.delete(
      typeof idOrIri === "string" && idOrIri.startsWith("/api/")
        ? idOrIri
        : `/api/third_parties/${idOrIri}`,
    ),

  updateExchange: (idOrIri, payload) =>
    baseService.put(
      idOrIri.startsWith("/api/")
        ? idOrIri
        : `/api/third_party_data_exchanges/${idOrIri}`,
      payload,
    ),

  deleteExchange: (idOrIri) =>
    baseService.delete(
      idOrIri.startsWith("/api/")
        ? idOrIri
        : `/api/third_party_data_exchanges/${idOrIri}`,
    ),
}

import baseService from "./baseService"

export default {
  /**
   * @param {boolean} doNotListCampus
   * @returns {Promise<void>}
   */
  registerCampus: async (doNotListCampus) => {
    await baseService.post("/admin/register-campus", {
      donotlistcampus: doNotListCampus,
    })
  },

  /**
   * @returns {Promise<string>}
   */
  findAnnouncements: async () => {
    return await baseService.get("/main/inc/ajax/admin.ajax.php?a=get_latest_news")
  },

  /**
   * @returns {Promise<string>}
   */
  findVersion: async () => {
    return await baseService.get("/main/inc/ajax/admin.ajax.php?a=version")
  },

  /**
   * @returns {Promise<string>}
   */
  findSupport: async () => {
    return await baseService.get("/main/inc/ajax/admin.ajax.php?a=get_support")
  },

  /**
   * @returns {Promise<Object>}
   */
  findBlocks: async () => {
    return await baseService.get("/admin/index")
  },
}

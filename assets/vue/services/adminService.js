import api from "../config/api"

export default {
  /**
   * @param {boolean} doNotListCampus
   * @returns {Promise<void>}
   */
  registerCampus: async (doNotListCampus) => {
    await api.post("/admin/register-campus", {
      donotlistcampus: doNotListCampus,
    })
  },

  /**
   * @returns {Promise<string>}
   */
  findAnnouncements: async () => {
    const { data } = await api.get("/main/inc/ajax/admin.ajax.php?a=get_latest_news")

    return data
  },

  /**
   * @returns {Promise<string>}
   */
  findVersion: async () => {
    const { data } = await api.get("/main/inc/ajax/admin.ajax.php?a=version")

    return data
  },

  /**
   * @returns {Promise<Object>}
   */
  findBlocks: async () => {
    const { data } = await api.get("/admin/index")

    return data
  },
}

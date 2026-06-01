import baseService from "./baseService"

export default {
  /**
   * Fetches the list of public system announcements (news).
   * @returns {Promise<Object[]>}
   */
  list() {
    return baseService.get("/news/list")
  },
}

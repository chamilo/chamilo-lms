import baseService from "./baseService"

export default {
  /**
   * Fetches the current user's BuyCourses active services and purchase history.
   * @returns {Promise<Object>}
   */
  getMyServices() {
    return baseService.get("/my-services-data")
  },
}

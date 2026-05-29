import baseService from "./baseService"

export default {
  /**
   * Fetches the blocks rendered in a plugin region.
   * @param {string} region
   * @param {{params?: Object, signal?: AbortSignal}} [options={}]
   * @returns {Promise<Object>}
   */
  async getRegion(region, { params = {}, signal } = {}) {
    const response = await baseService.getRaw(`/plugin-regions/${region}`, {
      params,
      signal,
    })

    return response.data
  },
}

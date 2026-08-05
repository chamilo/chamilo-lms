import makeService from "./api"
import baseService from "./baseService"

export default makeService("resource_nodes", {
  /**
   * Fetches a single resource node by id.
   * @param {number|string} id
   * @returns {Promise<Object>}
   */
  async findById(id) {
    return baseService.get(`/api/resource_nodes/${id}`)
  },
})

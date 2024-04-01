import api from "../config/api"

export default {
  async create(params) {
    const { data } = await api.post("/api/resource_links", params)

    return data
  },
}

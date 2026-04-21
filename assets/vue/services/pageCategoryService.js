import axios from "axios"

export default {
  findAll: async () => {
    const response = await axios.get("/api/page_categories")

    return response.data["hydra:member"]
  },
}

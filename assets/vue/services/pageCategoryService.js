import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

export default {
  findAll: async () => {
    const response = await axios.get(ENTRYPOINT + "page_categories")

    return response.data["hydra:member"]
  },
}

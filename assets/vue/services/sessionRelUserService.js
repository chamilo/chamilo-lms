import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

const sessionRelUserService = {
  findAll: (params) => axios.get(ENTRYPOINT + "session_rel_users", { params }).then((response) => response.data),
}

export default sessionRelUserService

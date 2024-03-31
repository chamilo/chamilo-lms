import baseService from "./baseService"

async function findAll(params) {
  return await baseService.getCollection("/api/session_rel_users", params)
}

export default {
  findAll,
}

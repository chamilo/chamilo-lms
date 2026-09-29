import baseService from "./baseService"

const ENDPOINT = "/api/oauth_connected_apps"

async function list() {
  const { items } = await baseService.getCollection(ENDPOINT)

  return items
}

async function revoke(id) {
  return baseService.delete(`${ENDPOINT}/${id}`)
}

export default {
  list,
  revoke,
}

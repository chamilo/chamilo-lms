import baseService from "./baseService"

const ENDPOINT = "/api/mcp_api_key"

async function getCurrent() {
  return baseService.get(ENDPOINT)
}

async function generate() {
  return baseService.post(ENDPOINT, {})
}

async function revoke() {
  return baseService.delete(ENDPOINT)
}

export default {
  getCurrent,
  generate,
  revoke,
}

import baseService from "./baseService"

export default {
  status() {
    return baseService.get("/geocoding/status")
  },
  lookup(address) {
    return baseService.get("/geocoding/lookup", { address })
  },
}

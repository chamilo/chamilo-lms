import makeService from "./api"
import baseService from "./baseService"

const branchService = makeService("branches")

branchService.fetchWithCounts = async () => {
  return baseService.get("/admin/branches/with-counts")
}

export default branchService

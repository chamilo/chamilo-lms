import makeService from "./api"
import baseService from "./baseService"

// CourseCategory only exposes a Patch operation (no Put) -- makeService()'s
// built-in update() defaults to PUT, which would 404/405 here. Override it,
// keeping the Response-like `{ json() }` shape store/modules/crud.js expects.
async function update(payload) {
  const data = await baseService.patch(payload["@id"], payload)

  return { json: async () => data }
}

export default makeService("course_categories", { update })

export async function findAll() {
  const { items } = await baseService.getCollection("/api/course_categories", { pagination: false })

  return items
}

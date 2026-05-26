import makeService from "./api"
import baseService from "./baseService"

export default makeService("course_categories")

export async function findAll() {
  const { items } = await baseService.getCollection("/api/course_categories", { pagination: false })

  return items
}

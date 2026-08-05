import baseService from "./baseService"

export default {
  findAll: async () => {
    const { items } = await baseService.getCollection("/api/page_categories")

    return items
  },
}

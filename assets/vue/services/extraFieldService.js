import baseService from "./baseService"

export async function getAll(params = {}) {
  const { items } = await baseService.getCollection("/api/extra_fields", { pagination: false, ...params })

  return items
}

export async function getByItemType(itemType, params = {}) {
  return getAll({ itemType, ...params })
}

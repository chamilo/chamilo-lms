import baseService from "./baseService"

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== "" && value !== 0),
  )
}

function config(params = {}) {
  return { params: cleanParams(params) }
}

export default {
  getList(params = {}) {
    return baseService.get("/api/course-groups/list", cleanParams(params))
  },

  getOverview(params = {}) {
    return baseService.get("/api/course-groups/overview", cleanParams(params))
  },

  getForm(groupId = 0, params = {}) {
    const suffix = groupId > 0 ? `/${groupId}` : ""
    return baseService.get(`/api/course-groups/form${suffix}`, cleanParams(params))
  },

  saveForm(payload, groupId = 0, params = {}) {
    const suffix = groupId > 0 ? `/${groupId}` : ""
    return baseService.post(`/api/course-groups/form${suffix}`, payload, {}, config(params))
  },

  getCategoryForm(categoryId = 0, params = {}) {
    const suffix = categoryId > 0 ? `/${categoryId}` : ""
    return baseService.get(`/api/course-groups/categories/form${suffix}`, cleanParams(params))
  },

  saveCategoryForm(payload, categoryId = 0, params = {}) {
    const suffix = categoryId > 0 ? `/${categoryId}` : ""
    return baseService.post(`/api/course-groups/categories/form${suffix}`, payload, {}, config(params))
  },

  getMembers(groupId, mode, params = {}) {
    return baseService.get(`/api/course-groups/${groupId}/${mode}`, cleanParams(params))
  },

  saveMembers(groupId, mode, selectedIds, params = {}) {
    return baseService.post(`/api/course-groups/${groupId}/${mode}`, { selectedIds }, {}, config(params))
  },

  getDetail(groupId, params = {}) {
    return baseService.get(`/api/course-groups/${groupId}/detail`, cleanParams(params))
  },

  getImport(params = {}) {
    return baseService.get("/api/course-groups/import", cleanParams(params))
  },

  importGroups(file, deleteMissing, params = {}) {
    const formData = new FormData()
    formData.append("file", file)
    formData.append("deleteMissing", deleteMissing ? "1" : "0")
    return baseService.post("/api/course-groups/import", formData, {}, config(params))
  },

  action(name, payload, params = {}) {
    return baseService.post(`/api/course-groups/actions/${name}`, payload, {}, config(params))
  },
}

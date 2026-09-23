import baseService from "./baseService"

const cleanParams = (params = {}) =>
  Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== null && String(value) !== "" && Number(value) !== 0),
  )

const getItems = async (params = {}) => await baseService.get("/api/toolbox/items", cleanParams(params))

const getItem = async (itemId, params = {}) =>
  await baseService.get(`/api/toolbox/items/${Number(itemId)}`, cleanParams(params))

const createItem = async (params, payload) =>
  await baseService.post("/api/toolbox/items", payload, {}, { params: cleanParams(params) })

const generateVersion = async (itemId, params, payload) =>
  await baseService.post(`/api/toolbox/items/${Number(itemId)}/generate`, payload, {}, { params: cleanParams(params) })

const setVisibility = async (itemId, params, visible) =>
  await baseService.post(
    `/api/toolbox/items/${Number(itemId)}/visibility`,
    { visible: Boolean(visible) },
    {},
    { params: cleanParams(params) },
  )

const restoreVersion = async (itemId, versionNumber, params) =>
  await baseService.post(
    `/api/toolbox/items/${Number(itemId)}/versions/${Number(versionNumber)}/restore`,
    {},
    {},
    { params: cleanParams(params) },
  )

const restartAttempt = async (learningPathId, params) =>
  await baseService.post(
    `/api/learning_paths/${Number(learningPathId)}/runtime/restart`,
    {},
    {},
    { params: cleanParams(params) },
  )

export default {
  getItems,
  getItem,
  createItem,
  generateVersion,
  setVisibility,
  restoreVersion,
  restartAttempt,
}

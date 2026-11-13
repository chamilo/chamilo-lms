import baseService from "./baseService"

function query(params) {
  return new URLSearchParams({ cid: String(params.cid), sid: String(params.sid || 0) }).toString()
}

export default {
  getConfiguration(params) {
    return baseService.get("/api/course-automation/configuration", params)
  },
  addAction(params, payload) {
    return baseService.post(`/api/course-automation/actions?${query(params)}`, payload)
  },
  execute(params, eventType) {
    return baseService.post(`/api/course-automation/execute?${query(params)}`, { eventType })
  },
  deleteAction(params, actionId) {
    return baseService.delete(`/api/course-automation/actions/${Number(actionId)}?${query(params)}`)
  },
}

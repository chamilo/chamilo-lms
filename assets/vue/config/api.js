import axios from "axios"
import { getRawCourseContext } from "../utils/courseContext"

/**
 * @type {axios.AxiosInstance}
 */
const instance = axios.create({
  headers: {
    Accept: "application/ld+json",
    // Soft hint so the backend may return HTML for XHR errors (kept from the
    // global axios defaults previously set in plugins/httpErrors.js).
    "X-Prefer-HTML-Errors": "1",
  },
})

// Add cid/sid/gid automatically to every API call so requests keep the current
// course/session/group context. The values come from getRawCourseContext(), the
// same source the getCourseContext composable and the services use, so they cannot diverge.
instance.interceptors.request.use((config) => {
  const { cid: pageCid, sid: pageSid, gid: pageGid } = getRawCourseContext()

  if (!pageCid && !pageSid && !pageGid) {
    return config
  }

  // Only for API calls
  const url = config.url || ""
  if (!url.includes("/api/")) {
    return config
  }

  // Ensure params is an object
  config.params = { ...(config.params || {}) }

  // Inject/override context, especially if request has cid=0
  if (
    pageCid &&
    (config.params.cid === undefined ||
      config.params.cid === null ||
      config.params.cid === "" ||
      String(config.params.cid) === "0")
  ) {
    config.params.cid = pageCid
  }
  if (pageSid && (config.params.sid === undefined || config.params.sid === null || config.params.sid === "")) {
    config.params.sid = pageSid
  }
  if (pageGid && (config.params.gid === undefined || config.params.gid === null || config.params.gid === "")) {
    config.params.gid = pageGid
  }

  return config
})

export default instance

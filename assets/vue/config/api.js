import axios from "axios"

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

// Add cid/sid/gid automatically to every API call, mirroring the global axios
// interceptor configured in main.js. Instances created via axios.create() do not
// inherit interceptors from the default axios instance, so it must be set here too
// for baseService-based requests to keep the current course/session/group context.
instance.interceptors.request.use((config) => {
  const sp = new URLSearchParams(window.location.search)
  const pageCid = sp.get("cid")
  const pageSid = sp.get("sid")
  const pageGid = sp.get("gid")

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

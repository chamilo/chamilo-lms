import axios from "axios"
import { getRawCourseContext } from "../utils/courseContext"

/**
 * @type {axios.AxiosInstance}
 */
const instance = axios.create({
  headers: {
    // Accept is set per-request by the interceptor below, based on whether the
    // path is served by API Platform (/api/*) or by a plain Symfony controller.
    // Soft hint so the backend may return HTML for XHR errors (kept from the
    // global axios defaults previously set in plugins/httpErrors.js).
    "X-Prefer-HTML-Errors": "1",
  },
})

// Accept values the interceptor is allowed to overwrite. Anything else (e.g.
// "application/zip" for binary downloads) is treated as an explicit caller
// choice and left untouched.
const MANAGED_ACCEPT = new Set([
  "application/json",
  "application/ld+json",
  // Default Accept axios injects when the caller sets none.
  "application/json, text/plain, */*",
])

/**
 * Extracts the pathname from a request URL, dropping origin, query and hash.
 * @param {string} url
 * @returns {string}
 */
function getPathname(url) {
  if (!url) {
    return ""
  }

  let path = url

  const schemeIndex = path.indexOf("://")
  if (-1 !== schemeIndex) {
    const afterScheme = path.slice(schemeIndex + 3)
    const slashIndex = afterScheme.indexOf("/")
    path = -1 === slashIndex ? "/" : afterScheme.slice(slashIndex)
  }

  const cutIndex = path.search(/[?#]/)

  return -1 === cutIndex ? path : path.slice(0, cutIndex)
}

/**
 * Tells whether a path is served by API Platform (and therefore speaks JSON-LD).
 * Everything else is a plain Symfony controller that speaks plain JSON.
 * @param {string} path
 * @returns {boolean}
 */
function isApiPlatformPath(path) {
  return "/api" === path || path.startsWith("/api/")
}

// Negotiate the response format automatically: API Platform resources get
// JSON-LD/Hydra (which the services rely on), plain controllers get JSON.
// An Accept header explicitly chosen by the caller is preserved.
instance.interceptors.request.use((config) => {
  const path = getPathname(config.url)
  const accept = isApiPlatformPath(path) ? "application/ld+json" : "application/json"

  const headers = config.headers
  const current = "function" === typeof headers?.get ? headers.get("Accept") : headers?.Accept

  if (null === current || undefined === current || MANAGED_ACCEPT.has(String(current))) {
    if ("function" === typeof headers?.set) {
      headers.set("Accept", accept)
    } else {
      config.headers = { ...(config.headers || {}), Accept: accept }
    }
  }

  return config
})

// Add cid/sid/gid automatically to every API call so requests keep the current
// course/session/group context. The values come from getRawCourseContext(), the
// same source the getCourseContext composable and the services use, so they cannot diverge.
instance.interceptors.request.use((config) => {
  // Opt-out: global requests (topbar/sidebar widgets, etc.) can disable course
  // context injection even while the user is inside a course.
  if (config.skipCourseContext) {
    return config
  }

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

// Single source of truth for reading the course/session/group context
// (cid/sid/gid) from the current browser URL.
//
// It mirrors the router's resolveCourseId() (assets/vue/router/index.js): on the
// CourseHome route the course id lives in the path (/course/:id/home) instead of
// the query string. Keeping the same resolution here guarantees these values stay
// consistent with the cidReq store (fed by the router guards) and the getCourseContext
// composable (which delegates to this helper).
//
// This module has no Vue/router imports on purpose, so it can be used anywhere —
// the API request interceptor, plain services and components alike — without
// circular-import or setup()-context constraints.

// Matches the CourseHome route path "/course/:id/home".
const COURSE_HOME_PATH = /^\/course\/(\d+)\/home/

/**
 * Reads the raw course context from the current URL.
 *
 * Returns raw string values (or null when absent) so callers can distinguish
 * "missing" from "0" — required by the API request interceptor.
 *
 * @returns {{cid: string|null, sid: string|null, gid: string|null}}
 */
export function getRawCourseContext() {
  const search = new URLSearchParams(window.location.search)

  const pathMatch = window.location.pathname.match(COURSE_HOME_PATH)
  const cid = pathMatch ? pathMatch[1] : search.get("cid")

  return {
    cid,
    sid: search.get("sid"),
    gid: search.get("gid"),
  }
}

/**
 * Reads the course context parsed to numbers (0 when absent), matching the shape
 * components and services expect.
 *
 * @returns {{cid: number, sid: number, gid: number}}
 */
export function getCourseContext() {
  const { cid, sid, gid } = getRawCourseContext()

  return {
    cid: parseInt(cid ?? 0) || 0,
    sid: parseInt(sid ?? 0) || 0,
    gid: parseInt(gid ?? 0) || 0,
  }
}

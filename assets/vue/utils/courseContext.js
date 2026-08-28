// Single source of truth for reading the course/session/group context
// (cid/sid/gid) from a URL — the current browser URL or a target route location.
//
// On some course-scoped routes the course id lives in the path (for example
// /course/:id/home and /ai/course/:id/analyzer) instead of the query string.
// Centralizing that rule here guarantees these
// values stay consistent everywhere: the router guards (which resolve the cid
// of the *target* route via resolveCourseIdFromRoute, since window.location
// still points to the previous route during navigation), the cidReq store
// (fed by those guards) and the getCourseContext composable.
//
// This module has no Vue/router imports on purpose, so it can be used anywhere —
// the API request interceptor, plain services and components alike — without
// circular-import or setup()-context constraints.

// Matches routes where the course id is carried in the path instead of `?cid=`.
const COURSE_ID_PATHS = [
  /^\/course\/(\d+)\/home/,
  /^\/ai\/course\/(\d+)\/analyzer/,
]

/**
 * Resolves the raw course id from a pathname and the cid query value:
 * the path wins on the CourseHome route, the query string everywhere else.
 *
 * @param {string} pathname
 * @param {string|null} queryCid
 * @returns {string|null}
 */
function resolveRawCid(pathname, queryCid) {
  for (const pathPattern of COURSE_ID_PATHS) {
    const pathMatch = pathname.match(pathPattern)

    if (pathMatch) {
      return pathMatch[1]
    }
  }

  return queryCid
}

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

  return {
    cid: resolveRawCid(window.location.pathname, search.get("cid")),
    sid: search.get("sid"),
    gid: search.get("gid"),
  }
}

/**
 * Resolves the course id from a router route location (target of a navigation).
 * Guards must use this instead of getCourseContext(): during navigation,
 * window.location still points to the previous route.
 *
 * @param {{path?: string, query?: Object}} to - vue-router route location
 * @returns {number} the course id, or 0 when absent
 */
export function resolveCourseIdFromRoute(to) {
  return parseInt(resolveRawCid(to?.path ?? "", to?.query?.cid) ?? 0) || 0
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

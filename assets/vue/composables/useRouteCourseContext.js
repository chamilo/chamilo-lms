import { computed } from "vue"
import { useRoute } from "vue-router"
import { getCourseContext } from "../utils/courseContext"

function normalizeContextId(value, allowZero = false) {
  const rawValue = Array.isArray(value) ? value[0] : value

  if (rawValue === undefined || rawValue === null || rawValue === "") {
    return 0
  }

  const normalizedValue = Number(rawValue)

  if (!Number.isInteger(normalizedValue)) {
    return 0
  }

  if (allowZero) {
    return Math.max(0, normalizedValue)
  }

  return normalizedValue > 0 ? normalizedValue : 0
}

export function useRouteCourseContext() {
  const route = useRoute()
  const courseContext = getCourseContext()

  const cid = computed(() => normalizeContextId(route.query.cid) || normalizeContextId(courseContext.cid.value))
  const sid = computed(() => {
    if (Object.prototype.hasOwnProperty.call(route.query, "sid")) {
      return normalizeContextId(route.query.sid, true)
    }

    return normalizeContextId(courseContext.sid.value, true)
  })
  const gid = computed(() => {
    if (Object.prototype.hasOwnProperty.call(route.query, "gid")) {
      return normalizeContextId(route.query.gid, true)
    }

    return normalizeContextId(courseContext.gid.value, true)
  })
  const contextQuery = computed(() => ({
    ...route.query,
    cid: cid.value || undefined,
    sid: sid.value,
    gid: gid.value,
  }))

  return {
    cid,
    sid,
    gid,
    contextQuery,
  }
}

import { defineStore } from "pinia"
import { ref } from "vue"

// Avoid refetching the course-home tools grid (and flashing its skeleton) when the user
// enters a tool by mistake and immediately returns via the breadcrumb.
const CACHE_TTL_MS = 120000

// Keyed by role (not just courseId/sessionId): the same user can see two different payloads
// for the same course (teacher view vs. student view, via the student-view toggle), so the
// role must be part of the key or a stale privileged snapshot could resurface after toggling.
function buildKey(courseId, sessionId, role) {
  return `${courseId}:${sessionId || 0}:${role}`
}

export const useCourseHomeToolsStore = defineStore("courseHomeTools", () => {
  const entries = ref({})

  function getFresh(courseId, sessionId, role) {
    const entry = entries.value[buildKey(courseId, sessionId, role)]

    return entry && Date.now() - entry.fetchedAt < CACHE_TTL_MS ? entry : null
  }

  function setEntry(courseId, sessionId, role, data) {
    entries.value[buildKey(courseId, sessionId, role)] = { ...data, fetchedAt: Date.now() }
  }

  function invalidate(courseId, sessionId, role) {
    delete entries.value[buildKey(courseId, sessionId, role)]
  }

  return { getFresh, setEntry, invalidate }
})

const STORAGE_PREFIX = "chamilo:assignment-submission-sort:v1"

export function getAssignmentSortStorageKey(view, userId, { cid, sid = 0, gid = 0 }) {
  const user = Number(userId)
  const course = Number(cid)
  if (!Number.isSafeInteger(user) || user <= 0 || !Number.isSafeInteger(course) || course <= 0) {
    return null
  }

  return `${STORAGE_PREFIX}:${user}:${course}:${Number(sid) || 0}:${Number(gid) || 0}:${view}`
}

export function loadAssignmentSort(storageKey, allowedFields, defaultField, defaultOrder) {
  const fallback = { field: defaultField, order: defaultOrder }
  if (!storageKey || typeof window === "undefined") return fallback

  try {
    const stored = JSON.parse(window.sessionStorage.getItem(storageKey))
    if (allowedFields.includes(stored?.field) && (stored?.order === 1 || stored?.order === -1)) {
      return { field: stored.field, order: stored.order }
    }
  } catch {
    // Storage may be blocked or contain obsolete data; keep the default sort.
  }

  return fallback
}

export function saveAssignmentSort(storageKey, field, order) {
  if (!storageKey || typeof window === "undefined") return

  try {
    window.sessionStorage.setItem(storageKey, JSON.stringify({ field, order }))
  } catch {
    // Browsing must continue when browser storage is unavailable.
  }
}

// The list view of an assignment (one row per learner, or every submission) is kept for the
// session under the same kind of key as the sort, built with getAssignmentSortStorageKey("view", ...).
export function loadAssignmentView(storageKey, allowedViews, defaultView) {
  if (!storageKey || typeof window === "undefined") return defaultView

  try {
    const stored = JSON.parse(window.sessionStorage.getItem(storageKey))
    if (allowedViews.includes(stored)) {
      return stored
    }
  } catch {
    // Storage may be blocked or contain obsolete data; keep the default view.
  }

  return defaultView
}

export function saveAssignmentView(storageKey, view) {
  if (!storageKey || typeof window === "undefined") return

  try {
    window.sessionStorage.setItem(storageKey, JSON.stringify(view))
  } catch {
    // Browsing must continue when browser storage is unavailable.
  }
}

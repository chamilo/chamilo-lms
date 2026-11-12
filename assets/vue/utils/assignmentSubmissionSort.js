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

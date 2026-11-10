const PENDING_CORRECTIONS_ORIGIN = "pending-corrections"
const RETURN_FILTER_KEYS = [
  "courseId",
  "exerciseId",
  "filterByUser",
  "status",
  "questionTypeId",
  "startDate",
  "endDate",
]

function firstQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

export function isPendingCorrectionsOrigin(query) {
  return firstQueryValue(query?.from) === PENDING_CORRECTIONS_ORIGIN
}

export function pendingCorrectionsReturnQuery(query) {
  const filters = { status: 6 }

  for (const key of RETURN_FILTER_KEYS) {
    const value = firstQueryValue(query?.[`pending_${key}`])
    if (value !== undefined && value !== null && String(value) !== "") {
      filters[key] = value
    }
  }

  return filters
}

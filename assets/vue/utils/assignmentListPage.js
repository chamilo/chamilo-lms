/**
 * Keep session assignments server-paginated, followed by pending base-course originals.
 * Originals are metadata only: their keys must never be treated as resource IRIs.
 */
export function mergeAssignmentPage({ sessionItems, sessionTotal, baseItems, page, pageSize, titleOrder }) {
  const direction = titleOrder === "desc" ? -1 : 1
  const originals = baseItems
    .filter((item) => !item.copied)
    .sort((a, b) => direction * (a.title.localeCompare(b.title) || a.id - b.id))
  const offset = (page - 1) * pageSize
  const originalOffset = Math.max(0, offset - sessionTotal)
  const sessionSlots = Math.max(0, Math.min(pageSize, sessionTotal - offset))
  const originalSlots = pageSize - sessionSlots
  const originalRows = originals.slice(originalOffset, originalOffset + originalSlots).map((item) => ({
    ...item,
    "@id": `base-assignment:${item.id}`,
    isBaseCourseAssignment: true,
  }))

  return {
    items: [...sessionItems, ...originalRows],
    total: sessionTotal + originals.length,
  }
}

/**
 * Polygon points are normalized to the image, not geographical coordinates.
 * Leaflet CRS.Simple uses [lat, lng] = [image bottom-to-top, image left-to-right].
 */
export const IMAGE_UNITS = 1000

export function imageUnits(width, height) {
  if (!Number.isFinite(width) || !Number.isFinite(height) || width <= 0 || height <= 0) return null
  const max = Math.max(width, height)
  return { width: (IMAGE_UNITS * width) / max, height: (IMAGE_UNITS * height) / max }
}

export function isRoomPolygon(map) {
  return map?.type === "polygon" && Array.isArray(map.points) &&
    map.points.length >= 3 && map.points.length <= 100 &&
    map.points.every((point) => Array.isArray(point) && point.length === 2 &&
      point.every((value) => typeof value === "number" && Number.isFinite(value) && value >= 0 && value <= 1))
}

export function toLeafletPoints(map, units = { width: IMAGE_UNITS, height: IMAGE_UNITS }) {
  if (!isRoomPolygon(map)) return []
  return map.points.map(([x, y]) => [units.height * (1 - y), units.width * x])
}

export function fromLeafletPoints(latLngs, units = { width: IMAGE_UNITS, height: IMAGE_UNITS }) {
  if (!Array.isArray(latLngs) || latLngs.length < 3 || latLngs.length > 100) return null
  const points = latLngs.map((p) => [
    Number((p.lng / units.width).toFixed(6)),
    Number((1 - p.lat / units.height).toFixed(6)),
  ])
  const map = { type: "polygon", points }
  return isRoomPolygon(map) ? map : null
}

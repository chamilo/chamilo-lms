import assert from "node:assert/strict"
import test from "node:test"
import { fromLeafletPoints, imageUnits, isRoomPolygon, toLeafletPoints } from "../../assets/vue/components/room/roomFloorPlanGeometry.mjs"

test("normalized polygons survive a Leaflet CRS.Simple conversion", () => {
  // An existing SVG-saved map must be readable by Leaflet.draw without changing storage.
  const old = { type: "polygon", points: [[0.799118, 0.099508], [0.271411, 0.119306], [0.31738, 0.66764], [0.754408, 0.677239]] }
  assert.deepEqual(fromLeafletPoints(toLeafletPoints(old).map(([lat, lng]) => ({ lat, lng }))), old)
})

test("image top-left, bottom-right and centre map to the correct latitude/longitude", () => {
  const polygon = { type: "polygon", points: [[0, 0], [1, 1], [0.5, 0.5]] }
  assert.deepEqual(toLeafletPoints(polygon), [[1000, 0], [0, 1000], [500, 500]])
})

test("rectangular source images keep their aspect ratio while preserving coordinates", () => {
  const units = imageUnits(1600, 800)
  assert.deepEqual(units, { width: 1000, height: 500 })
  const polygon = { type: "polygon", points: [[0, 0], [1, 0], [0.75, 0.75]] }
  assert.deepEqual(toLeafletPoints(polygon, units), [[500, 0], [500, 1000], [125, 750]])
  assert.deepEqual(fromLeafletPoints(toLeafletPoints(polygon, units).map(([lat, lng]) => ({ lat, lng })), units), polygon)
})

test("out-of-bounds and malformed polygons are rejected before form save", () => {
  assert.equal(fromLeafletPoints([{ lat: 1001, lng: 0 }, { lat: 1000, lng: 1 }, { lat: 500, lng: 500 }]), null)
  assert.equal(fromLeafletPoints([{ lat: 1000, lng: 0 }, { lat: 200, lng: 1000 }]), null)
  assert.equal(isRoomPolygon({ type: "polygon", points: [[0, 0], [1, 0], [Infinity, 1]] }), false)
})

test("the editor preserves all legacy room polygons and limits vertices", () => {
  const map = { type: "polygon", points: Array.from({ length: 100 }, (_, i) => [i / 100, 0.5]) }
  assert.equal(isRoomPolygon(map), true)
  assert.equal(isRoomPolygon({ ...map, points: [...map.points, [1, 1]] }), false)
})

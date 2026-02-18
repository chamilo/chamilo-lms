import makeService from "./api"
import baseService from "./baseService"

const roomService = makeService("rooms")

roomService.fetchWithCounts = async () => {
  return baseService.get("/admin/rooms/with-counts")
}

roomService.getInfo = async (id) => {
  return baseService.get(`/admin/rooms/${id}/info`)
}

roomService.getOccupation = async (id, start, end) => {
  return baseService.get(`/admin/rooms/${id}/occupation`, {
    start: start.toISOString(),
    end: end.toISOString(),
  })
}

roomService.findAvailable = async (start, end) => {
  return baseService.get("/admin/rooms/availability", {
    start: start.toISOString(),
    end: end.toISOString(),
  })
}

roomService.exists = async () => {
  const { totalItems } = await baseService.getCollection("/api/rooms", { itemsPerPage: 1 })
  return totalItems > 0
}

export default roomService

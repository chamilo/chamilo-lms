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

roomService.getCourses = async (id) => {
  return baseService.get(`/admin/rooms/${id}/courses`)
}

roomService.exists = async () => {
  const { totalItems } = await baseService.getCollection("/api/rooms", { itemsPerPage: 1 })
  return totalItems > 0
}

roomService.getOptions = async ({
  includeDefault = false,
  defaultLabel = "Use default room",
  floorLabel = "Floor",
  capacityLabel = "Capacity",
} = {}) => {
  const { items } = await baseService.getCollection("/api/rooms", { itemsPerPage: 1000 })

  const options = items.map((room) => {
    const details = []

    if (room.branch?.title) {
      details.push(room.branch.title)
    }

    details.push(room.title)

    if (room.floorNumber !== null && room.floorNumber !== undefined) {
      details.push(`${floorLabel} ${room.floorNumber}`)
    }

    if (room.capacity !== null && room.capacity !== undefined) {
      details.push(`${capacityLabel} ${room.capacity}`)
    }

    return {
      label: details.join(" — "),
      value: room["@id"],
      room,
    }
  })

  if (includeDefault) {
    options.unshift({ label: defaultLabel, value: null, room: null })
  }

  return options
}

export default roomService

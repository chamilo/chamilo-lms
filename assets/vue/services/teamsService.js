import baseService from "./baseService"

const cleanParams = (params = {}) =>
  Object.fromEntries(
    Object.entries(params).filter(
      ([, value]) => value !== undefined && value !== null && String(value) !== "" && Number(value) !== 0,
    ),
  )

const getMeetings = async (params = {}) => await baseService.get("/api/conference/teams/meetings", cleanParams(params))

const getMeeting = async (meetingId, params = {}) =>
  await baseService.get(`/api/conference/teams/meetings/${Number(meetingId)}`, cleanParams(params))

const createMeeting = async (params, payload) =>
  await baseService.post("/api/conference/teams/meetings", payload, {}, { params: cleanParams(params) })

const updateMeeting = async (meetingId, params, payload) =>
  await baseService.post(
    `/api/conference/teams/meetings/${Number(meetingId)}/update`,
    payload,
    {},
    { params: cleanParams(params) },
  )

const cancelMeeting = async (meetingId, params = {}) =>
  await baseService.post(
    `/api/conference/teams/meetings/${Number(meetingId)}/cancel`,
    {},
    {},
    { params: cleanParams(params) },
  )

const getProtectedJoinUrl = (meetingId) => {
  const id = Number(meetingId)
  if (!Number.isInteger(id) || id <= 0) {
    return ""
  }

  return new URL(`/conference/teams-auth/join/${id}`, window.location.origin).toString()
}

const downloadCalendarFile = (meeting) => {
  const meetingId = Number(meeting?.id ?? 0)
  const startAt = meeting?.startAt ? new Date(meeting.startAt) : null
  const endAt = meeting?.endAt ? new Date(meeting.endAt) : null
  const joinUrl = getProtectedJoinUrl(meetingId)

  if (
    !Number.isInteger(meetingId) ||
    meetingId <= 0 ||
    !(startAt instanceof Date) ||
    Number.isNaN(startAt.getTime()) ||
    !(endAt instanceof Date) ||
    Number.isNaN(endAt.getTime()) ||
    !joinUrl
  ) {
    return false
  }

  const title = String(meeting?.title || "Microsoft Teams")
  const hostname = window.location.hostname || "chamilo"
  const uid = `chamilo-teams-${meetingId}@${hostname}`
  const now = new Date()
  const description = `Microsoft Teams\n${joinUrl}`

  const content = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//Chamilo//Microsoft Teams//EN",
    "CALSCALE:GREGORIAN",
    "METHOD:PUBLISH",
    "BEGIN:VEVENT",
    `UID:${escapeIcs(uid)}`,
    `DTSTAMP:${formatIcsDate(now)}`,
    `DTSTART:${formatIcsDate(startAt)}`,
    `DTEND:${formatIcsDate(endAt)}`,
    `SUMMARY:${escapeIcs(title)}`,
    `DESCRIPTION:${escapeIcs(description)}`,
    `URL:${escapeIcs(joinUrl)}`,
    "END:VEVENT",
    "END:VCALENDAR",
    "",
  ].join("\r\n")

  const blob = new Blob([content], { type: "text/calendar;charset=utf-8" })
  const objectUrl = URL.createObjectURL(blob)
  const anchor = document.createElement("a")
  anchor.href = objectUrl
  anchor.download = `${safeFilename(title)}.ics`
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(objectUrl)

  return true
}

function formatIcsDate(date) {
  return date
    .toISOString()
    .replace(/[-:]/g, "")
    .replace(/\.\d{3}Z$/, "Z")
}

function escapeIcs(value) {
  return String(value ?? "")
    .replaceAll("\\", "\\\\")
    .replaceAll("\r\n", "\\n")
    .replaceAll("\n", "\\n")
    .replaceAll(",", "\\,")
    .replaceAll(";", "\\;")
}

function safeFilename(value) {
  const normalized = String(value ?? "Microsoft Teams")
    .trim()
    .replace(/[\\/:*?"<>|]+/g, "-")
    .replace(/\s+/g, " ")

  return normalized || "Microsoft Teams"
}

export default {
  getMeetings,
  getMeeting,
  createMeeting,
  updateMeeting,
  cancelMeeting,
  getProtectedJoinUrl,
  downloadCalendarFile,
}

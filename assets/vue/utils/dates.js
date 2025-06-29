import { DateTime } from "luxon"
import { useFormatDate } from "../composables/formatDate"

const { getCurrentTimezone } = useFormatDate()

/**
 * Format a JS Date object to string using the current or provided timezone.
 * @param {Date} date - JavaScript Date object
 * @param {string} [timezone] - Optional timezone (e.g. "America/Lima")
 * @returns {string}
 */
const formatDateTime = function (date, timezone) {
  if (!date) return ""
  const tz = timezone || getCurrentTimezone()
  return DateTime.fromJSDate(date, { zone: "utc" }).setZone(tz).toFormat("dd/LL/yyyy HH:mm")
}

/**
 * Format an ISO string to readable string using the current or provided timezone.
 * @param {string} dateStr - ISO date string (e.g. "2025-06-17T14:00:00Z")
 * @param {string} [timezone] - Optional timezone
 * @returns {string}
 */
const formatDateTimeFromISO = function (dateStr, timezone) {
  if (!dateStr) return ""
  const tz = timezone || getCurrentTimezone()
  return DateTime.fromISO(dateStr, { zone: "utc" }).setZone(tz).toFormat("dd/LL/yyyy HH:mm")
}

export { formatDateTime, formatDateTimeFromISO }

const { DateTime } = require("luxon")

export function useAbbreviatedDatetime(datetime) {
  if (!datetime) {
    return ""
  }

  return DateTime.fromISO(datetime).toLocaleString({
    ...DateTime.DATETIME_MED,
    month: "long",
  })
}

export function useRelativeDatetime(datetime) {
  return DateTime.fromISO(datetime).toRelative()
}

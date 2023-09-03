const { DateTime } = require("luxon")

export function useFormatDate() {
  const abbreviatedDatetime = (datetime) => {
    if (!datetime) {
      return ""
    }

    return DateTime.fromISO(datetime).toLocaleString({
      ...DateTime.DATETIME_MED,
      month: "long",
    })
  }

  const relativeDatetime = (datetime) => DateTime.fromISO(datetime).toRelative()

  return {
    abbreviatedDatetime,
    relativeDatetime,
  }
}

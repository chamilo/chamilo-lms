import { DateTime } from "luxon"
import { useLocale } from "./locale"

export function useFormatDate() {
  const { appParentLocale } = useLocale()

  /**
   * @param {Date|string} datetime
   * @returns {DateTime|null}
   */
  function getDateTimeObject(datetime) {
    if (!datetime) {
      return null
    }

    let dt

    if (typeof datetime === "string") {
      dt = DateTime.fromISO(datetime)
    } else if (typeof datetime === "object") {
      dt = DateTime.fromJSDate(datetime)
    }

    if (!dt.isValid) {
      return null
    }

    return dt.setLocale(appParentLocale.value)
  }

  const abbreviatedDatetime = (datetime) =>
    getDateTimeObject(datetime)?.toLocaleString({
      ...DateTime.DATETIME_MED,
      month: "long",
    })

  const relativeDatetime = (datetime) => getDateTimeObject(datetime)?.toRelative()

  return {
    abbreviatedDatetime,
    relativeDatetime,
  }
}

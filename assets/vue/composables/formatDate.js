import { DateTime } from "luxon"
import { useLocale } from "./locale"

export function useFormatDate() {
  const { appParentLocale } = useLocale()

  const abbreviatedDatetime = (datetime) => {
    if (!datetime) {
      return ""
    }

    return DateTime.fromISO(datetime)
      .setLocale(appParentLocale.value)
      .toLocaleString({
        ...DateTime.DATETIME_MED,
        month: "long",
      })
  }

  const relativeDatetime = (datetime) => DateTime.fromISO(datetime).setLocale(appParentLocale.value).toRelative()

  return {
    abbreviatedDatetime,
    relativeDatetime,
  }
}

import { DateTime } from "luxon"
import { useLocale } from "./locale"
import { usePlatformConfig } from "../store/platformConfig"
import { useSecurityStore } from "../store/securityStore"

export function useFormatDate() {
  const { appParentLocale } = useLocale()
  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()

  function getCurrentTimezone() {
    const allowUserTimezone = platformConfigStore.getSetting("profile.use_users_timezone") === "true"
    const userTimezone = securityStore.user?.timezone
    const platformTimezone = platformConfigStore.getSetting("platform.timezone")

    if (allowUserTimezone && userTimezone) {
      return userTimezone
    }

    if (platformTimezone && platformTimezone !== "false") {
      return platformTimezone
    }

    return Intl.DateTimeFormat().resolvedOptions().timeZone
  }

  function getDateTimeObject(datetime) {
    if (!datetime) {
      return null
    }

    let dt

    if (typeof datetime === "string") {
      dt = DateTime.fromISO(datetime, { zone: "utc" })
    } else if (datetime instanceof Date) {
      dt = DateTime.fromJSDate(datetime, { zone: "utc" })
    } else {
      return null
    }

    if (!dt.isValid) {
      return null
    }

    return dt.setZone(getCurrentTimezone()).setLocale(appParentLocale.value)
  }

  const abbreviatedDatetime = (datetime) =>
    getDateTimeObject(datetime)?.toLocaleString({
      ...DateTime.DATETIME_MED,
      month: "long",
    })

  const relativeDatetime = (datetime) =>
    getDateTimeObject(datetime)?.toRelative()

  return {
    abbreviatedDatetime,
    relativeDatetime,
    getCurrentTimezone,
  }
}

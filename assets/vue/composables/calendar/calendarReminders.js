import { useI18n } from "vue-i18n"

export function useCalendarReminders() {
  const { t } = useI18n()

  const periodList = [
    { label: t("Minutes"), value: "i" },
    { label: t("Hours"), value: "h" },
    { label: t("Days"), value: "d" },
  ]

  /**
   * @param {Object} reminder
   * @returns {string}
   */
  function decodeDateInterval(reminder) {
    if (reminder.period === "i") {
      return t("%d minutes before", [reminder.count])
    }

    if (reminder.period === "h") {
      return t("%d hours before", [reminder.count])
    }

    return t("%d days before", [reminder.count])
  }

  return {
    periodList,
    decodeDateInterval,
  }
}

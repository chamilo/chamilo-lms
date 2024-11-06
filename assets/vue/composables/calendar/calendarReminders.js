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
    let unit;
    if (reminder.period === "i") {
      unit = t("minutes before");
    } else if (reminder.period === "h") {
      unit = t("hours before");
    } else {
      unit = t("days before");
    }

    return `${reminder.count} ${unit}`;
  }

  return {
    periodList,
    decodeDateInterval,
  }
}

import { useConfirm } from "primevue/useconfirm"
import { useI18n } from "vue-i18n"

export function useConfirmation() {
  const confirm = useConfirm()
  const { t } = useI18n()

  const requireConfirmation = ({ title, message, accept, reject } = {}) => {
    confirm.require({
      header: title ?? t("Confirmation"),
      message: message ?? t("Please confirm your choice"),
      rejectProps: {
        label: t("Cancel"),
        outlined: true,
        severity: "contrast",
      },
      acceptProps: {
        label: t("Yes"),
        severity: "secondary",
      },
      accept,
      reject,
    })
  }

  return { requireConfirmation }
}

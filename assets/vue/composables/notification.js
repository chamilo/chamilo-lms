import { useToast } from "primevue/usetoast"

export function useNotification() {
  const toast = useToast()

  const showSuccessNotification = (message) => {
    showMessage(message, "success")
  }

  const showInfoNotification = (error) => {
    showMessage(error, "info")
  }

  const showWarningNotification = (message) => {
    showMessage(message, "warn")
  }

  const showErrorNotification = (error) => {
    let message = error

    if (error.response) {
      if (error.response.data) {
        if (error.response.data["hydra:description"]) {
          message = error.response.data["hydra:description"]
        } else if (error.response.data.error) {
          message = error.response.data.error
        }
      }
    } else if (error.message) {
      message = error.message
    } else if (typeof error === "string") {
      message = error
    }

    showMessage(message, "error")
  }

  const showMessage = (message, severity) => {
    toast.add({
      severity: severity,
      detail: message,
      life: "error" === severity ? undefined : 3500,
    })
  }

  return {
    showSuccessNotification,
    showInfoNotification,
    showWarningNotification,
    showErrorNotification,
  }
}

import { mapFields } from "vuex-map-fields"
import { useNotification } from "../composables/notification"

export default {
  setup() {
    const notification = useNotification()

    const showError = (error) => {
      notification.showErrorNotification(error)
    }

    const showMessage = (message, type = "success") => {
      switch (type) {
        case "info":
          notification.showInfoNotification(message)
          break
        case "success":
          notification.showSuccessNotification(message)
          break
        case "error":
        case "danger":
          notification.showErrorNotification(message)
          break
        case "warning":
          notification.showWarningNotification(message)
          break
      }
    }

    return {
      showError,
      showMessage,
    }
  },
  methods: {
    showError(error) {
      this.showMessage(error, "error") // Use 'error' for PrimeVue
    },
    showMessage(message, type = "success") {
      // Convert message type to PrimeVue's severity
      let severity = type
      if (type === "danger") {
        severity = "error" // PrimeVue uses 'error' instead of 'danger'
      }

      // Use PrimeVue's ToastService
      this.$toast.add({
        severity: severity,
        summary: message,
        detail: "",
        life: 5000, // Message duration in milliseconds
        closable: true, // Whether the message can be closed manually
      })
    },
  },
  computed: {
    ...mapFields("notifications", ["color", "show", "subText", "text", "timeout"]),
  },
}

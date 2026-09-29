import { useToast } from "primevue/usetoast"
import { sanitizeHtml } from "../utils/sanitizeHtml"

// Toast timings
const ERROR_TOAST_LIFE_MS = 30000
const DEFAULT_TOAST_LIFE_MS = 3500
const DUPLICATE_WINDOW_MS = 1200

// Module-level cache so duplicate protection works across composable calls
let lastToastSignature = null
let lastToastTimestamp = 0

function isSuspiciousErrorMessage(message) {
  if (typeof message !== "string") {
    return true
  }

  const value = message.trim()
  if (!value) {
    return true
  }

  // Common indicators of internal exception leakage
  const suspiciousPatterns = [
    "TypeError",
    "Exception",
    "ErrorException",
    "Stack trace",
    "Return value must be of type",
    "Symfony\\",
    "Chamilo\\",
    ".php",
    "line ",
    "#0 ",
  ]

  return suspiciousPatterns.some((pattern) => value.includes(pattern))
}

function normalizeMessage(message, fallback = "An unexpected error occurred.") {
  if (typeof message === "string" && message.trim()) {
    return message.trim()
  }

  // Non-string values (booleans, numbers, objects from a malformed backend
  // payload) must never be stringified into the toast — e.g. a boolean
  // `error: true` field would otherwise render the literal text "true".
  return fallback
}

function shouldSkipDuplicateToast(severity, message) {
  const now = Date.now()
  const signature = `${severity}::${message}`

  if (lastToastSignature === signature && now - lastToastTimestamp < DUPLICATE_WINDOW_MS) {
    return true
  }

  lastToastSignature = signature
  lastToastTimestamp = now

  return false
}

export function useNotification() {
  const toast = useToast()

  const showSuccessNotification = (message) => {
    showMessage(message, "success")
  }

  const showInfoNotification = (message) => {
    showMessage(message, "info")
  }

  const showWarningNotification = (message, options) => {
    showMessage(message, "warn", options)
  }

  const showErrorNotification = (error, options) => {
    let message = "Authentication failed. Please try again."

    // Axios-like error response
    if (error && error.response) {
      const status = error.response.status
      const data = error.response.data

      // Only trust backend messages for expected client/auth errors
      if ([400, 401, 403, 404, 409, 422, 429].includes(status)) {
        if (data && typeof data === "object") {
          if (typeof data["hydra:description"] === "string") {
            message = data["hydra:description"]
          } else if (typeof data.error === "string") {
            message = data.error
          } else if (typeof data.message === "string") {
            message = data.message
          }
        } else if (typeof data === "string") {
          message = data
        }
      } else {
        // Unexpected/server errors should not expose raw backend details
        message = "An unexpected error occurred. Please try again later."
      }
    } else if (error && typeof error.message === "string") {
      // Plain JS error (sanitize it)
      message = error.message
    } else if (typeof error === "string") {
      message = error
    }

    message = normalizeMessage(message, "An unexpected error occurred. Please try again later.")

    // Final safety net against internal exception leakage
    if (isSuspiciousErrorMessage(message)) {
      message = "An unexpected error occurred. Please try again later."
    }

    showMessage(message, "error", options)
  }

  /**
   * @param {string} message
   * @param {string} severity - PrimeVue severity: success | info | warn | error.
   * @param {Object} [options]
   * @param {boolean} [options.persistent] - Keep the toast until the user closes it.
   * @returns {void}
   */
  const showMessage = (message, severity, { persistent = false } = {}) => {
    const safeMessage = normalizeMessage(
      message,
      "error" === severity ? "An unexpected error occurred. Please try again later." : "Notification",
    )

    // Prevent duplicate toasts fired by local + global handlers at the same time
    if (shouldSkipDuplicateToast(severity, safeMessage)) {
      return
    }

    // PrimeVue only starts the auto-close timer when `life` is truthy.
    const life = "error" === severity ? ERROR_TOAST_LIFE_MS : DEFAULT_TOAST_LIFE_MS

    toast.add({
      severity,
      detail: sanitizeHtml(safeMessage),
      life: persistent ? null : life,
      baseZIndex: 10,
    })
  }

  return {
    showSuccessNotification,
    showInfoNotification,
    showWarningNotification,
    showErrorNotification,
  }
}

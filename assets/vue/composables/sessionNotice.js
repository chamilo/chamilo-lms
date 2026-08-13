import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"
import i18n from "../i18n"

// Module-level: several boot-time requests can fail at once when the session
// has expired (check-session, check-enrollments, next-session, CidReqListener
// denials, ...). Without this gate each one would show its own warning.
let notified = false

export function resetSessionNotice() {
  notified = false
}

/**
 * Wording for a session the server no longer recognizes.
 *
 * When the user is still identified client-side the UI locale is the right one.
 * Otherwise nothing is known about their language, so the platform language is
 * forced instead.
 *
 * @returns {string}
 */
export function sessionLostMessage() {
  const securityStore = useSecurityStore()
  const key = "Your session details have been lost, please login again."

  if (securityStore.isAuthenticated) {
    return i18n.global.t(key)
  }

  const platformConfigStore = usePlatformConfig()
  const platformLocale = platformConfigStore.getSetting("language.platform_language")
  const options = platformLocale ? { locale: platformLocale } : {}

  return i18n.global.t(key, {}, options)
}

/**
 * Single entry point for permission/session warnings raised by a denied request
 * (403). Only the first caller in an episode gets to display something; the rest
 * are no-ops. Wording depends on whether a user could still be identified
 * client-side, not on which endpoint or HTTP status happened to fail first —
 * those are inconsistent per backend call site and unreliable to branch on.
 *
 * Lost sessions do not come through here: they are published as state by the
 * session interceptor (see plugins/sessionExpiry.js) and rendered once by the
 * root component, so they need no gate of their own.
 *
 * @param {(message: string) => void} display
 * @returns {void}
 */
export function notifySessionIssue(display) {
  if (notified) {
    return
  }
  notified = true

  const securityStore = useSecurityStore()

  if (securityStore.isAuthenticated) {
    display(
      i18n.global.t(
        "Access to this resource has been denied. You don't seem to have the necessary permissions to access it.",
      ),
    )

    return
  }

  display(sessionLostMessage())
}

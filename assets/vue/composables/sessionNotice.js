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
 * Single entry point for every session/auth-related warning (expired session,
 * unknown user, insufficient privileges). Only the first caller in an episode
 * gets to display something; the rest are no-ops. Wording and locale depend on
 * whether a user could still be identified client-side, not on which endpoint
 * or HTTP status happened to fail first — those are inconsistent per backend
 * call site and unreliable to branch on.
 *
 * @param {(message: string) => void} display
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

  const platformConfigStore = usePlatformConfig()
  const platformLocale = platformConfigStore.getSetting("language.platform_language")
  const options = platformLocale ? { locale: platformLocale } : {}

  display(i18n.global.t("Your session details have been lost, please login again.", {}, options))
}

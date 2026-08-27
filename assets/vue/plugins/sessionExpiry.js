import api from "../config/api"
import { useSecurityStore } from "../store/securityStore"

const SAFE_RETRY_METHODS = new Set(["get", "head", "options"])
let sessionRecoveryPromise = null

/**
 * Revalidates the browser session through the main firewall.
 *
 * This intentionally uses fetch instead of the shared axios instance so a 401
 * from /check-session cannot recurse through this interceptor. When a valid
 * remember-me cookie exists, the backend can restore the Symfony session on
 * this request and return the authenticated user again.
 *
 * @param {ReturnType<typeof useSecurityStore>} securityStore
 * @returns {Promise<boolean>}
 */
function recoverSession(securityStore) {
  if (sessionRecoveryPromise) {
    return sessionRecoveryPromise
  }

  sessionRecoveryPromise = fetch("/check-session", {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      Accept: "application/json",
    },
  })
    .then(async (response) => {
      if (!response.ok) {
        return false
      }

      const data = await response.json()

      if (!data?.isAuthenticated || !data?.user) {
        return false
      }

      securityStore.setUser(data.user)
      securityStore.clearSessionLost()

      return true
    })
    .catch(() => false)
    .finally(() => {
      sessionRecoveryPromise = null
    })

  return sessionRecoveryPromise
}

/**
 * Publishes "the session is gone" as store state only after the backend has
 * confirmed that the browser session cannot be recovered.
 *
 * A single API request can return 401 after the PHP session expires while a
 * valid remember-me cookie is still available. A full page refresh would then
 * restore the user, so showing a definitive "session lost" warning immediately
 * is misleading. Revalidate once through /check-session first; when recovery
 * succeeds, retry only idempotent requests and leave non-idempotent callers to
 * decide whether they should be repeated.
 *
 * The recovery call bypasses axios to avoid interceptor recursion. Concurrent
 * 401 responses share one recovery promise so they do not create a request
 * storm or race several remember-me rotations.
 *
 * @returns {void}
 */
export default function installSessionExpiry() {
  api.interceptors.response.use(
    (response) => response,
    async (error) => {
      if (401 !== error?.response?.status) {
        return Promise.reject(error)
      }

      const securityStore = useSecurityStore()

      if (!securityStore.isAuthenticated) {
        return Promise.reject(error)
      }

      const requestConfig = error?.config

      // A request retried after a successful session recovery may still have
      // endpoint-specific authentication requirements. Do not turn that into a
      // global "session lost" warning.
      if (requestConfig?.__chamiloSessionRecoveryAttempted) {
        return Promise.reject(error)
      }

      const recovered = await recoverSession(securityStore)

      if (!recovered) {
        securityStore.markSessionLost()

        return Promise.reject(error)
      }

      if (!requestConfig) {
        return Promise.reject(error)
      }

      const method = String(requestConfig.method || "get").toLowerCase()

      if (!SAFE_RETRY_METHODS.has(method)) {
        return Promise.reject(error)
      }

      requestConfig.__chamiloSessionRecoveryAttempted = true

      return api(requestConfig)
    },
  )
}

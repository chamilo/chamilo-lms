import api from "../config/api"
import { useSecurityStore } from "../store/securityStore"

/**
 * Publishes "the session is gone" as store state whenever the server answers
 * 401 to a client that believed it was authenticated.
 *
 * Only the session axis is handled globally; every other status is left to the
 * caller's own catch, which already reports it through showErrorNotification.
 * A 401 while the client knows it is anonymous is never a lost session: it is a
 * failed sign-in, or a widget asking for something an anonymous visitor cannot
 * have. That single condition replaces any list of endpoints or routes.
 *
 * This lives outside config/api.js on purpose: importing the store there would
 * close the cycle config/api -> securityStore -> securityService -> baseService
 * -> config/api. It must be installed once Pinia is active (see main.js).
 *
 * @returns {void}
 */
export default function installSessionExpiry() {
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (401 === error?.response?.status) {
        const securityStore = useSecurityStore()

        if (securityStore.isAuthenticated) {
          securityStore.markSessionLost()
        }
      }

      return Promise.reject(error)
    },
  )
}
